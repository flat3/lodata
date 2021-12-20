<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Annotation\Capabilities\V1\DeepInsertSupport;
use Flat3\Lodata\ComputedProperty;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\SQL\SQLConnection;
use Flat3\Lodata\Drivers\SQL\SQLExpression;
use Flat3\Lodata\Drivers\SQL\SQLSchema;
use Flat3\Lodata\Drivers\SQL\SQLWhere;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Helper\PropertyValues;
use Flat3\Lodata\Interfaces\EntitySet\ComputeInterface;
use Flat3\Lodata\Interfaces\EntitySet\CountInterface;
use Flat3\Lodata\Interfaces\EntitySet\CreateInterface;
use Flat3\Lodata\Interfaces\EntitySet\DeleteInterface;
use Flat3\Lodata\Interfaces\EntitySet\ExpandInterface;
use Flat3\Lodata\Interfaces\EntitySet\FilterInterface;
use Flat3\Lodata\Interfaces\EntitySet\OrderByInterface;
use Flat3\Lodata\Interfaces\EntitySet\PaginationInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\EntitySet\ReadInterface;
use Flat3\Lodata\Interfaces\EntitySet\SearchInterface;
use Flat3\Lodata\Interfaces\EntitySet\UpdateInterface;
use Flat3\Lodata\Interfaces\TransactionInterface;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\Property;
use Flat3\Lodata\ReferentialConstraint;
use Flat3\Lodata\Transaction\NavigationRequest;
use Generator;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use PDOStatement;

/**
 * SQL Entity Set
 * @package Flat3\Lodata\Drivers
 */
class SQLEntitySet extends EntitySet implements CountInterface, CreateInterface, DeleteInterface, ExpandInterface, FilterInterface, OrderByInterface, PaginationInterface, QueryInterface, ReadInterface, SearchInterface, TransactionInterface, UpdateInterface, ComputeInterface
{
    use SQLConnection;
    use SQLSchema;
    use SQLWhere {
        generateWhere as protected sqlGenerateWhere;
    }

    public const PostgreSQL = 'pgsql';
    public const MySQL = 'mysql';
    public const SQLite = 'sqlite';
    public const SQLServer = 'sqlsrv';

    /**
     * Mapping of OData properties to source identifiers
     * @var ObjectArray $sourceMap
     */
    protected $sourceMap;

    /**
     * Database connection name
     * @var string $connection
     */
    protected $connection = null;

    /**
     * Database table for this entity sett
     * @var string $table
     */
    private $table;

    public function __construct(string $name, EntityType $entityType)
    {
        parent::__construct($name, $entityType);

        $this->sourceMap = new ObjectArray();
        $this->addAnnotation(new DeepInsertSupport());
    }

    /**
     * Get the connection name
     * @return string Name
     */
    public function getConnectionName(): ?string
    {
        return $this->connection;
    }

    /**
     * Set the connection name
     * @param  string  $connection  Name
     * @return $this
     */
    public function setConnectionName(string $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Get the database connection
     * @return ConnectionInterface|Connection Connection
     */
    public function getConnection(): ConnectionInterface
    {
        return DB::connection($this->getConnectionName());
    }

    /**
     * Get the table name used by this entity set
     * @return string Table name
     */
    public function getTable(): string
    {
        return $this->table ?: $this->identifier->getName();
    }

    /**
     * Set the table name used by this entity set
     * @param  string  $table  Table name
     * @return $this
     */
    public function setTable(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Convert the provided property to a qualified database field
     * @param  Property  $property  Property
     * @return SQLExpression Expression
     */
    public function propertyToExpression(Property $property): SQLExpression
    {
        $expression = new SQLExpression($this);

        switch (true) {
            case $property instanceof DeclaredProperty:
                $expression->pushStatement(
                    sprintf(
                        '%s.%s',
                        $this->getTable(),
                        $this->quoteSingleIdentifier($this->getPropertySourceName($property))
                    )
                );
                break;

            case $property instanceof ComputedProperty:
                $expression->pushStatement($this->quoteSingleIdentifier($property->getName()));
                break;
        }

        return $expression;
    }

    /**
     * Get the underlying database field name for the given entity type property
     * @param  Property  $property  Property
     * @return string Field name
     */
    public function getPropertySourceName(Property $property): string
    {
        return $this->sourceMap[$property] ?? $property->getName();
    }

    /**
     * Set an underlying database field name for the given entity type property
     * @param  Property  $property  Property
     * @param  string  $sourceName  Field name
     * @return $this
     */
    public function setPropertySourceName(Property $property, string $sourceName): self
    {
        $this->sourceMap[$property] = $sourceName;

        return $this;
    }

    /**
     * Read an SQL record
     * @param  PropertyValue  $key  Key
     * @return Entity|null Entity
     */
    public function read(PropertyValue $key): ?Entity
    {
        $expression = new SQLExpression($this);
        $expression->pushStatement('SELECT');

        $columns = $this->getColumnsToQuery();
        while ($columns) {
            $field = array_shift($columns);
            $expression->pushExpression($field);

            if ($columns) {
                $expression->pushComma();
            }
        }

        $expression->pushStatement(sprintf('FROM %s WHERE', $this->getTable()));
        $expression->pushExpression($this->propertyToExpression($key->getProperty()));
        $expression->pushStatement('=?');
        $expression->pushParameter($key->getPrimitiveValue());

        $stmt = $this->pdoSelect($expression);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (false === $result) {
            return null;
        }

        return $this->newEntity()->fromSource($this->coerceTypes($result));
    }

    /**
     * Count the number of records matching the query
     * @return int Count
     */
    public function count(): int
    {
        $query = $this->pdoSelect($this->getCountExpression());

        return (int) $query->fetchColumn();
    }

    /**
     * Generate a PDO-compatible SQL query that modifies the database
     * @param  SQLExpression  $expression  Query container
     * @return string|null Affected row ID
     */
    private function pdoModify(SQLExpression $expression): ?string
    {
        $dbh = $this->getHandle();

        try {
            $stmt = $dbh->prepare($expression->getStatement());
            $this->bindParameters($stmt, $expression);
            $stmt->execute();
        } catch (PDOException $e) {
            throw new InternalServerErrorException(
                'query_error',
                sprintf('The executed query returned an error: %s', $e->getMessage())
            );
        }

        return $dbh->lastInsertId();
    }

    /**
     * Generate a PDO-compatible SQL query that selects from the database
     * @param  SQLExpression  $expression  Query expression
     * @return PDOStatement PDO statement handle
     */
    private function pdoSelect(SQLExpression $expression): PDOStatement
    {
        $dbh = $this->getHandle();

        try {
            $stmt = $dbh->prepare($expression->getStatement());
            $this->bindParameters($stmt, $expression);
            $stmt->execute();
        } catch (PDOException $e) {
            throw new InternalServerErrorException(
                'query_error',
                sprintf('The executed query returned an error: %s', $e->getMessage())
            );
        }

        return $stmt;
    }

    /**
     * Apply the generated bind parameters to the provided PDO statement
     * @param  PDOStatement  $stmt  PDO statement handle
     * @param  SQLExpression  $expression  Expression
     */
    protected function bindParameters(PDOStatement $stmt, SQLExpression $expression)
    {
        $parameters = $expression->getParameters();

        if (!$parameters) {
            return;
        }

        foreach ($parameters as $key => $value) {
            $stmt->bindValue(
                is_string($key) ? $key : $key + 1,
                $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
    }

    /**
     * Get a version of the query string that counts the total number of rows in the collection
     * @return SQLExpression Query expression
     */
    public function getCountExpression(): SQLExpression
    {
        $expression = new SQLExpression($this);
        $expression->pushStatement(sprintf('SELECT COUNT(*) FROM %s', $this->getTable()));

        $where = $this->generateWhere();

        if ($where->hasStatement()) {
            $expression->pushStatement('WHERE');
            $expression->pushExpression($where);
        }

        return $expression;
    }

    /**
     * Run a PDO query and return the results
     */
    public function query(): Generator
    {
        $stmt = $this->pdoSelect($this->getResultExpression());

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $this->newEntity()->fromSource($this->coerceTypes($row));
        }
    }

    /**
     * Generate the where clause for this query
     */
    public function generateWhere(): SQLExpression
    {
        $where = $this->sqlGenerateWhere();

        if (!$this->navigationPropertyValue) {
            return $where;
        }

        if ($where->hasStatement()) {
            $where->pushStatement('AND');
        }

        $key = $this->resolveExpansionKey();
        $fieldExpression = $this->propertyToExpression($key->getProperty());
        $where->pushExpression($fieldExpression);
        $where->pushStatement('=?');
        $where->pushParameter($key->getPrimitiveValue());

        return $where;
    }

    /**
     * Get the query string representing the query result
     * @return SQLExpression Query expression
     */
    public function getResultExpression(): SQLExpression
    {
        $expression = new SQLExpression($this);
        $expression->pushStatement('SELECT');

        $columns = $this->getColumnsToQuery();

        while ($columns) {
            $column = array_shift($columns);
            $expression->pushExpression($column);

            if ($columns) {
                $expression->pushComma();
            }
        }

        $expression->pushStatement(sprintf("FROM %s", $this->getTable()));

        $where = $this->generateWhere();

        if ($where->hasStatement()) {
            $expression->pushStatement('WHERE');
            $expression->pushExpression($where);
        }

        $orderby = $this->getOrderBy();

        if ($orderby->hasValue()) {
            $properties = $this->getType()->getDeclaredProperties();

            $compute = $this->getCompute();

            if ($compute->hasValue()) {
                $properties = $properties::merge($properties, $compute->getProperties());
            }

            $ob = implode(', ', array_map(function ($o) use ($properties) {
                [$literal, $direction] = $o;

                if (!$properties->get($literal)) {
                    throw new BadRequestException(
                        'invalid_orderby_property',
                        sprintf('The provided property %s was not found in this entity type', $literal)
                    );
                }

                return $this->quoteSingleIdentifier($literal)." $direction";
            }, $orderby->getSortOrders()));

            $expression->pushStatement(sprintf("ORDER BY %s", $ob));
        }

        if ($this->getSkip()->hasValue()) {
            $expression->pushStatement('LIMIT ? OFFSET ?');
            $expression->pushParameter($this->getTop()->hasValue() ? $this->getTop()->getValue() : PHP_INT_MAX);
            $expression->pushParameter($this->getSkip()->getValue());
        }

        return $expression;
    }

    /**
     * Determine the list of columns to include in the query result
     * @return SQLExpression[]
     */
    protected function getColumnsToQuery(): array
    {
        $properties = $this->getSelectedProperties()->sliceByClass(DeclaredProperty::class);

        $key = $this->getType()->getKey();

        if ($key && !$properties[$key]) {
            $properties[] = $key;
        }

        $navigationRequests = $this->getTransaction()->getNavigationRequests();

        foreach ($this->getType()->getNavigationProperties() as $navigationProperty) {
            /** @var NavigationRequest $navigationRequest */
            $navigationRequest = $navigationRequests->get($navigationProperty->getName());

            if (!$navigationRequest) {
                continue;
            }

            foreach ($navigationProperty->getConstraints() as $constraint) {
                $property = $constraint->getProperty();

                if (!$properties[$property]) {
                    $properties[] = $property;
                }
            }
        }

        $compute = $this->getCompute();

        if ($compute->hasValue()) {
            $computedProperties = $compute->getProperties();

            foreach ($computedProperties as $computedProperty) {
                $properties->add($computedProperty);
            }
        }

        $columns = [];

        foreach ($properties as $property) {
            switch (true) {
                case $property instanceof ComputedProperty:
                    $expression = new SQLExpression($this);
                    $computeParser = $this->getComputeParser();
                    $computeParser->pushEntitySet($this);
                    $tree = $computeParser->generateTree($property->getExpression());
                    $expression->evaluate($tree);
                    break;

                default:
                    $expression = $this->propertyToExpression($property);
                    break;
            }

            $expression->pushStatement(sprintf("AS %s", $this->quoteSingleIdentifier($property->getName())));

            $columns[] = $expression;
        }

        if (!$columns) {
            throw new InternalServerErrorException(
                'empty_property_set',
                'There are no properties to return in this query'
            );
        }

        return $columns;
    }

    /**
     * Create a new record
     * @param  PropertyValues  $propertyValues  Property values
     * @return Entity Entity
     */
    public function create(PropertyValues $propertyValues): Entity
    {
        $expressions = [];

        $declaredProperties = $this->getType()->getDeclaredProperties();

        /** @var DeclaredProperty $declaredProperty */
        foreach ($declaredProperties as $declaredProperty) {
            if (!$propertyValues->exists($declaredProperty)) {
                continue;
            }

            $expression = new SQLExpression($this);
            $expression->pushStatement($this->quoteSingleIdentifier($this->getPropertySourceName($declaredProperty)));
            $expression->pushParameter($propertyValues[$declaredProperty->getName()]->getPrimitiveValue());
            $expressions[] = $expression;
        }

        if ($this->navigationPropertyValue) {
            /** @var NavigationProperty $navigationProperty */
            $navigationProperty = $this->navigationPropertyValue->getProperty();

            /** @var ReferentialConstraint $constraint */
            foreach ($navigationProperty->getConstraints() as $constraint) {
                $referencedProperty = $constraint->getReferencedProperty();
                $expression = new SQLExpression($this);
                $expression->pushStatement($this->quoteSingleIdentifier($this->getPropertySourceName($referencedProperty)));
                $expression->pushParameter($this->navigationPropertyValue->getParent()->getEntityId()->getPrimitiveValue());
                $expressions[] = $expression;
            }
        }

        if (!$expressions) {
            throw new BadRequestException(
                'missing_fields',
                'The supplied object had no fields'
            );
        }

        $expression = new SQLExpression($this);
        $expression->pushStatement(sprintf("INSERT INTO %s", $this->getTable()));
        $fieldCount = count($expressions);

        $expression->pushStatement('(');

        while ($expressions) {
            $field = array_shift($expressions);
            $expression->pushExpression($field);

            if ($expressions) {
                $expression->pushComma();
            }
        }

        $expression->pushStatement(')');

        $expression->pushStatement('VALUES ('.implode(',', array_fill(0, $fieldCount, '?')).')');

        $id = $this->pdoModify($expression);

        $entityId = $propertyValues[$this->getType()->getKey()] ?? null;

        if (!$entityId) {
            $entityId = new PropertyValue();
            $entityId->setProperty($this->getType()->getKey());
            $entityId->setValue($this->getType()->getKey()->getType()->instance($id));
        }

        return $this->read($entityId);
    }

    /**
     * Update an existing record
     * @param  PropertyValue  $key  Key
     * @param  PropertyValues  $propertyValues  Property values
     * @return Entity Entity
     */
    public function update(PropertyValue $key, PropertyValues $propertyValues): Entity
    {
        $expressions = [];

        foreach ($propertyValues->getDeclaredPropertyValues() as $propertyValue) {
            $expression = new SQLExpression($this);
            $expression->pushStatement(
                sprintf(
                    '%s=?',
                    $this->quoteSingleIdentifier($this->getPropertySourceName($propertyValue->getProperty()))
                )
            );
            $expression->pushParameter($propertyValue->getPrimitiveValue());
            $expressions[] = $expression;
        }

        if ($this->navigationPropertyValue) {
            /** @var NavigationProperty $navigationProperty */
            $navigationProperty = $this->navigationPropertyValue->getProperty();

            /** @var ReferentialConstraint $constraint */
            foreach ($navigationProperty->getConstraints() as $constraint) {
                $referencedProperty = $constraint->getReferencedProperty();
                $expression = new SQLExpression($this);
                $expression->pushStatement(
                    sprintf(
                        '%s=?',
                        $this->quoteSingleIdentifier($this->getPropertySourceName($referencedProperty))
                    )
                );
                $expression->pushParameter($this->navigationPropertyValue->getParent()->getEntityId()->getPrimitiveValue());
                $expressions[] = $expression;
            }
        }

        if ($expressions) {
            $expression = new SQLExpression($this);
            $expression->pushStatement(sprintf('UPDATE %s SET', $this->getTable()));

            while ($expressions) {
                $field = array_shift($expressions);
                $expression->pushExpression($field);

                if ($expressions) {
                    $expression->pushComma();
                }
            }

            $expression->pushStatement('WHERE');
            $expression->pushExpression($this->propertyToExpression($this->getType()->getKey()));
            $expression->pushStatement('=?');
            $expression->pushParameter($key->getPrimitiveValue());

            $this->pdoModify($expression);
        }

        return $this->read($key);
    }

    /**
     * Delete a record
     * @param  PropertyValue  $key  Key
     */
    public function delete(PropertyValue $key): void
    {
        $type = $this->getType();

        $expression = new SQLExpression($this);
        $expression->pushStatement(
            sprintf(
                "DELETE FROM %s WHERE %s=?",
                $this->getTable(),
                $this->quoteSingleIdentifier($this->getPropertySourceName($type->getKey()))
            )
        );
        $expression->pushParameter($key->getPrimitiveValue());

        $this->pdoModify($expression);
    }

    /**
     * Coerce types for the given record
     * @link https://www.php.net/manual/en/migration81.incompatible.php
     * @param  array  $record  Database record
     * @return array
     */
    public function coerceTypes(array $record): array
    {
        foreach ($this->getCompute()->getProperties() as $computedProperty) {
            $key = $computedProperty->getName();
            $value = $record[$key];

            if (is_string($value) && is_numeric($value)) {
                $value = json_decode($value);
            }

            $record[$key] = $value;
        }

        return $record;
    }

    /**
     * Set the transaction that applies to this entity set instance, and validate the transaction request
     * @param  Transaction  $transaction  Transaction
     * @return $this
     */
    public function setTransaction(Transaction $transaction): EntitySet
    {
        parent::setTransaction($transaction);
        $this->getResultExpression();

        return $this;
    }

    public function startTransaction()
    {
        $this->getHandle()->beginTransaction();
    }

    public function rollback()
    {
        $this->getHandle()->rollBack();
    }

    public function commit()
    {
        $this->getHandle()->commit();
    }
}
