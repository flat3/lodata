<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers\SQL;

use Carbon\Carbon;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types;
use Flat3\Lodata\Annotation\Core\V1\Computed;
use Flat3\Lodata\Annotation\Core\V1\ComputedDefaultValue;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\SQL\PDO\Doctrine;
use Flat3\Lodata\Exception\Protocol\ConfigurationException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\DBAL;
use Flat3\Lodata\Helper\Discovery;
use Flat3\Lodata\Type;
use Illuminate\Support\Arr;

/**
 * SQL Schema
 * @package Flat3\Lodata\Drivers\SQL
 */
trait SQLSchema
{
    /**
     * Discover SQL fields on this entity set as OData properties.
     * Uses Discovery::remember() to cache the full processed property descriptors
     * (not just the raw DBAL Table), avoiding per-request column-to-property conversion.
     * @return $this
     */
    public function discoverProperties()
    {
        $cacheKey = sprintf("sql.properties.%s.%s", $this->getConnection()->getName(), $this->getTable());

        $propertyDescriptors = (new Discovery)->remember($cacheKey, function () {
            return $this->buildPropertyDescriptors();
        });

        $type = $this->getType();

        // Hydrate cached descriptors into actual OData DeclaredProperty objects
        if (isset($propertyDescriptors['key'])) {
            $keyDesc = $propertyDescriptors['key'];
            $key = new DeclaredProperty($keyDesc['name'], $this->resolveType($keyDesc['type']));
            if ($keyDesc['computed']) {
                $key->addAnnotation(new Computed);
            }
            $type->setKey($key);
        }

        foreach ($propertyDescriptors['properties'] as $propDesc) {
            $property = new DeclaredProperty($propDesc['name'], $this->resolveType($propDesc['type']));
            $property->setNullable($propDesc['nullable']);

            if ($propDesc['has_default']) {
                $property->addAnnotation(new ComputedDefaultValue);
                if ($propDesc['default_value'] !== null) {
                    $property->setDefaultValue($propDesc['default_value']);
                }
                if ($propDesc['default_is_carbon_now'] ?? false) {
                    $property->setDefaultValue([Carbon::class, 'now']);
                }
            }

            if (isset($propDesc['source_name'])) {
                $this->setPropertySourceName($property, $propDesc['source_name']);
            }

            $type->addProperty($property);
        }

        return $this;
    }

    /**
     * Build a serializable array of property descriptors from the database schema.
     * This is the expensive part that we cache.
     * @return array
     */
    protected function buildPropertyDescriptors(): array
    {
        $table = $this->getDatabase()->listTableDetails($this->getTable());

        $columns = $table->getColumns();
        $indexes = $table->getIndexes();
        $blacklist = config('lodata.discovery.blacklist', []);
        $platform = $this->getDatabase()->getDatabasePlatform();

        $result = ['key' => null, 'properties' => []];

        // Find primary key
        foreach ($indexes as $index) {
            if (!$index->isPrimary()) {
                continue;
            }

            /** @var Column $column */
            $column = Arr::first($columns, function (Column $column) use ($index) {
                return $column->getName() === $index->getColumns()[0];
            });

            if (!$column) {
                continue;
            }

            $typeName = $this->columnToTypeName($column);
            if ($typeName === null) {
                throw new ConfigurationException(
                    'missing_key',
                    sprintf('The table %s had no resolvable key', $this->getTable())
                );
            }

            $result['key'] = [
                'name' => $this->resolveColumnName($column),
                'type' => $typeName,
                'computed' => $column->getAutoincrement(),
            ];
        }

        // Process remaining columns
        $keyName = $result['key'] ? $result['key']['name'] : null;

        foreach ($columns as $column) {
            $columnName = $this->resolveColumnName($column);

            if ($keyName && $columnName === $keyName) {
                continue;
            }

            if (in_array($column->getName(), $blacklist)) {
                continue;
            }

            $typeName = $this->columnToTypeName($column);
            if ($typeName === null) {
                continue;
            }

            $propDesc = [
                'name' => $columnName,
                'type' => $typeName,
                'nullable' => !$column->getNotnull(),
                'has_default' => (bool) $column->getDefault(),
                'default_value' => null,
                'default_is_carbon_now' => false,
            ];

            // Track source name if it differs from the resolved column name
            if ($columnName !== $column->getName()) {
                $propDesc['source_name'] = $column->getName();
            }

            if ($column->getDefault()) {
                $default = $column->getDefault();

                switch (true) {
                    // DBAL 4.x returns DefaultExpression objects instead of strings
                    case !is_string($default):
                        $propDesc['default_is_carbon_now'] = true;
                        break;

                    case $default === $platform->getCurrentTimestampSQL():
                        $propDesc['default_is_carbon_now'] = true;
                        break;

                    case $platform->getReservedKeywordsList()->isKeyword($default):
                        break;

                    default:
                        $propDesc['default_value'] = $default;
                        break;
                }
            }

            $result['properties'][] = $propDesc;
        }

        return $result;
    }

    /**
     * Map a DBAL column to an OData type name string (for caching).
     * @param Column $column
     * @return string|null
     */
    protected function columnToTypeName(Column $column): ?string
    {
        $columnType = $column->getType();

        switch (true) {
            case $columnType instanceof Types\BooleanType:
                return 'boolean';
            case $columnType instanceof Types\DateType:
                return 'date';
            case $columnType instanceof Types\DateTimeType:
                return 'datetimeoffset';
            case $columnType instanceof Types\DecimalType:
            case $columnType instanceof Types\FloatType:
                return 'decimal';
            case $columnType instanceof Types\SmallIntType:
                return $column->getUnsigned() ? 'uint16' : 'int16';
            case $columnType instanceof Types\IntegerType:
                return $column->getUnsigned() ? 'uint32' : 'int32';
            case $columnType instanceof Types\BigIntType:
                return $column->getUnsigned() ? 'uint64' : 'int64';
            case $columnType instanceof Types\TimeType:
                return 'timeofday';
            case $columnType instanceof Types\StringType:
            default:
                return 'string';
        }
    }

    /**
     * Resolve a type name string back to an OData Type instance.
     * @param string $typeName
     * @return Type
     */
    protected function resolveType(string $typeName): Type
    {
        switch ($typeName) {
            case 'boolean': return Type::boolean();
            case 'date': return Type::date();
            case 'datetimeoffset': return Type::datetimeoffset();
            case 'decimal': return Type::decimal();
            case 'uint16':
                return Lodata::getTypeDefinition(Type\UInt16::identifier) ? Type::uint16() : Type::int16();
            case 'int16': return Type::int16();
            case 'uint32':
                return Lodata::getTypeDefinition(Type\UInt32::identifier) ? Type::uint32() : Type::int32();
            case 'int32': return Type::int32();
            case 'uint64':
                return Lodata::getTypeDefinition(Type\UInt64::identifier) ? Type::uint64() : Type::int64();
            case 'int64': return Type::int64();
            case 'timeofday': return Type::timeofday();
            case 'string':
            default: return Type::string();
        }
    }

    /**
     * Resolve the name of a column, accounting for namespaced columns.
     * @param Column $column
     * @return string
     */
    protected function resolveColumnName(Column $column): string
    {
        return $column->getNamespaceName()
            ? ($column->getNamespaceName().'_'.$column->getShortestName($column->getNamespaceName()))
            : $column->getName();
    }

    /**
     * Convert an SQL column to an OData declared property
     * @param  Column  $column  SQL column
     * @return ?DeclaredProperty OData declared property
     */
    public function columnToDeclaredProperty(Column $column): ?DeclaredProperty
    {
        $columnType = $column->getType();

        switch (true) {
            case $columnType instanceof Types\BooleanType:
                $type = Type::boolean();
                break;

            case $columnType instanceof Types\DateType:
                $type = Type::date();
                break;

            case $columnType instanceof Types\DateTimeType:
                $type = Type::datetimeoffset();
                break;

            case $columnType instanceof Types\DecimalType:
            case $columnType instanceof Types\FloatType:
                $type = Type::decimal();
                break;

            case $columnType instanceof Types\SmallIntType:
                $type = $column->getUnsigned() && Lodata::getTypeDefinition(Type\UInt16::identifier) ? Type::uint16() : Type::int16();
                break;

            case $columnType instanceof Types\IntegerType:
                $type = $column->getUnsigned() && Lodata::getTypeDefinition(Type\UInt32::identifier) ? Type::uint32() : Type::int32();
                break;

            case $columnType instanceof Types\BigIntType:
                $type = $column->getUnsigned() && Lodata::getTypeDefinition(Type\UInt64::identifier) ? Type::uint64() : Type::int64();
                break;

            case $columnType instanceof Types\TimeType:
                $type = Type::timeofday();
                break;

            case $columnType instanceof Types\StringType:
            default:
                $type = Type::string();
                break;
        }

        $columnName = $column->getNamespaceName() ? ($column->getNamespaceName().'_'.$column->getShortestName($column->getNamespaceName())) : $column->getName();
        $property = new DeclaredProperty($columnName, $type);

        if ($columnName !== $column->getName()) {
            $this->setPropertySourceName($property, $column->getName());
        }

        return $property;
    }
}
