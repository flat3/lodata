<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers\SQL;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types;
use Flat3\Lodata\Annotation\Core\V1\Computed;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Type;
use Illuminate\Database\Connection;

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
        /** @var Connection $connection */
        $connection = $this->getConnection();
        $manager = $connection->getDoctrineSchemaManager();
        $details = $manager->listTableDetails($this->getTable());
        $columns = $details->getColumns();

        /** @var EntityType $type */
        $type = $this->getType();

        $indexes = $manager->listTableIndexes($this->getTable());
        foreach ($indexes as $index) {
            if (!$index->isPrimary()) {
                continue;
            }

            $column = $columns[$index->getColumns()[0]];
            $property = $this->columnToDeclaredProperty($column);

            if ($column->getAutoincrement()) {
                $property->addAnnotation(new Computed());
            }

            $type->setKey($property);
        }

        if (!$type->getKey()) {
            throw new InternalServerErrorException(
                'missing_primary_key',
                'The primary key of this table could not be detected'
            );
        }

        $blacklist = config('lodata.discovery.blacklist', []);

        foreach ($columns as $column) {
            $columnName = $column->getName();

            if ($columnName === $type->getKey()->getName()) {
                continue;
            }

            if (in_array($columnName, $blacklist)) {
                continue;
            }

            $notnull = $column->getNotnull();

            $type->addProperty(
                $this->columnToDeclaredProperty($column)->setNullable(!$notnull)
            );
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
