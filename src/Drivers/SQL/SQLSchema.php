<?php

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\Type;
use Illuminate\Database\Connection;

/**
 * SQL Schema
 * @package Flat3\Lodata\Drivers\SQL
 */
trait SQLSchema
{
    /**
     * Get list of defined type casts
     * @return array Type casts
     */
    public function getCasts(): array
    {
        return [];
    }

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
        $casts = $this->getCasts();

        /** @var EntityType $type */
        $type = $this->getType();

        $indexes = $manager->listTableIndexes($this->getTable());
        foreach ($indexes as $index) {
            if (!$index->isPrimary()) {
                continue;
            }

            $column = $columns[$index->getColumns()[0]];
            $columnName = $column->getName();
            $sqlType = $column->getType()->getName();

            if (array_key_exists($columnName, $casts)) {
                $sqlType = $casts[$columnName];
            }

            $type->setKey(
                new DeclaredProperty(
                    $columnName,
                    $this->sqlTypeToPrimitiveType($sqlType),
                )
            );
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

            $sqlType = $column->getType()->getName();
            $notnull = $column->getNotnull();

            if (array_key_exists($columnName, $casts)) {
                $sqlType = $casts[$columnName];
            }

            $type->addProperty(
                new DeclaredProperty(
                    $columnName,
                    $this->sqlTypeToPrimitiveType($sqlType)->setNullable(!$notnull)
                )
            );
        }

        return $this;
    }

    /**
     * Convert an SQL type to an OData primitive type
     * @param  string  $type  SQL type
     * @return PrimitiveType OData type
     */
    public function sqlTypeToPrimitiveType(string $type): PrimitiveType
    {
        switch ($type) {
            case 'bool':
            case 'boolean':
                return Type::boolean();

            case 'date':
                return Type::date();

            case 'datetime':
                return Type::datetimeoffset();

            case 'decimal':
            case 'float':
            case 'real':
                return Type::decimal();

            case 'double':
                return Type::double();

            case 'int':
            case 'integer':
                return Type::int32();

            case 'varchar':
            case 'string':
                return Type::string();

            case 'timestamp':
                return Type::timeofday();
        }

        return Type::string();
    }
}
