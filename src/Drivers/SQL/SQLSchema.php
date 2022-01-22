<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers\SQL;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types;
use Flat3\Lodata\Annotation\Core\V1\Computed;
use Flat3\Lodata\Annotation\Core\V1\ComputedDefaultValue;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\Discovery;
use Flat3\Lodata\Type;
use Illuminate\Database\Connection;
use Illuminate\Support\Arr;

/**
 * SQL Schema
 * @package Flat3\Lodata\Drivers\SQL
 */
trait SQLSchema
{
    /**
     * Discover SQL fields on this entity set as OData properties
     * @return $this
     */
    public function discoverProperties()
    {
        $table = (new Discovery)->remember(
            sprintf("sql.%s.%s", $this->getConnection()->getName(), $this->getTable()),
            function () {
                /** @var Connection $connection */
                $connection = $this->getConnection();
                $manager = $connection->getDoctrineSchemaManager();
                return $manager->listTableDetails($this->getTable());
            }
        );

        $columns = $table->getColumns();
        $indexes = $table->getIndexes();

        $type = $this->getType();

        /** @var DeclaredProperty $key */
        $key = null;

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

            $key = $this->columnToDeclaredProperty($column);

            if ($column->getAutoincrement()) {
                $key->addAnnotation(new Computed);
            }

            $type->setKey($key);
        }

        $blacklist = config('lodata.discovery.blacklist', []);

        foreach ($columns as $column) {
            $columnName = $column->getName();

            if ($key && $columnName === $key->getName()) {
                continue;
            }

            if (in_array($columnName, $blacklist)) {
                continue;
            }

            $property = $this->columnToDeclaredProperty($column);
            $property->setNullable(!$column->getNotnull());

            if ($column->getDefault()) {
                $property->addAnnotation(new ComputedDefaultValue);
                $property->setDefaultValue($column->getDefault());
            }

            $type->addProperty($property);
        }

        return $this;
    }

    /**
     * Convert an SQL column to an OData declared property
     * @param  Column  $column  SQL column
     * @return DeclaredProperty OData declared property
     */
    public function columnToDeclaredProperty(Column $column): DeclaredProperty
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

        return new DeclaredProperty($column->getName(), $type);
    }
}
