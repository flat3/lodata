<?php

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\Type;
use Illuminate\Database\Connection;

trait SQLSchema
{
    public function getCasts(): array
    {
        return [];
    }

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

            $type->setKey(
                new DeclaredProperty(
                    $column->getName(),
                    $this->sqlTypeToPrimitiveType($column->getType()->getName())
                )
            );
        }

        foreach ($columns as $column) {
            $name = $column->getName();

            if ($name === $type->getKey()->getName()) {
                continue;
            }

            $cast = $column->getType()->getName();
            $notnull = $column->getNotnull();

            if (array_key_exists($name, $casts)) {
                $cast = $casts[$name];
            }

            $type->addProperty(
                new DeclaredProperty(
                    $name,
                    $this->sqlTypeToPrimitiveType($cast)->setNullable(!$notnull)
                )
            );
        }

        return $this;
    }

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
