<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Annotation\Capabilities\V1\DeepInsertSupport;
use Flat3\Lodata\ComputedProperty;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\SQL\SQLConnection;
use Flat3\Lodata\Drivers\SQL\SQLExpression;
use Flat3\Lodata\Drivers\SQL\SQLOrderBy;
use Flat3\Lodata\Drivers\SQL\SQLSchema;
use Flat3\Lodata\Drivers\SQL\SQLWhere;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Helper\JSON;
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
    use SQLOrderBy;
    use SQLSchema;
    use SQLWhere {
        generateWhere as protected sqlGenerateWhere;
    }

    public const PostgreSQL = 'pgsql';
    public const MySQL = 'mysql';
    public const SQLite = 'sqlite';
    public const SQLServer = 'sqlsrv';

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
        $expression = $this->getSQLExpression();

        switch (true) {
            case $property instanceof DeclaredProperty:
                $expression->pushStatement(
                    sprintf(
                        '%s.%s',
                        $this->quoteSingleIdentifier($this->getTable()),
                        $this->quoteSingleIdentifier($this->getPropertySourceName($property))
                    )
                );
                break;

            case $property instanceof ComputedProperty:
                $computedExpression = $this->getSQLExpression();
                $computeParser = $this->getComputeParser();
                $computeParser->pushEntitySet($this);
                $tree = $computeParser->generateTree($property->getExpression());
                $computedExpression->evaluate($tree);
                $expression->pushExpression($computedExpression);
                break;
        }

        return $expression;
    }

    /**
     * Read an SQL record
     * @param  PropertyValue  $key  Key
     * @return Entity|null Entity
     */
    public function read(PropertyValue $key): Entity
    {
        $expression = $this->getSQLExpression();
        $expression->pushStatement('SELECT');

        $columns = $this->getColumnsToQuery();
        while ($columns) {
            $field = array_shift($columns);
            $expression->pushExpression($field);

            if ($columns) {
                $expression->pushComma();
            }
        }

        $expression->pushStatement(sprintf('FROM %s WHERE', $this->quoteSingleIdentifier($this->getTable())));
        $expression->pushExpression($this->propertyToExpression($key->getProperty()));
        $expression->pushStatement('=?');
        $expression->pushParameter($key->getPrimitive()->toMixed());

        $stmt = $this->pdoSelect($expression);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (false === $row) {
            throw new NotFoundException('entity_not_found', 'Entity not found');
        }

        return $this->toEntity($row);
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
                sprintf('The executed query returned an error: %s', $e->getMessage()),
                $e
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
                sprintf('The executed query returned an error: %s', $e->getMessage()),
                $e
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
        $expression = $this->getSQLExpression();
        $expression->pushStatement(sprintf('SELECT COUNT(*) FROM %s', $this->quoteSingleIdentifier($this->getTable())));

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
            yield $this->toEntity($row);
        }
    }

    /**
     * Generate the where clause for this query
     */
    public function generateWhere(): SQLExpression
    {
        $where = $this->sqlGenerateWhere();

        if (!$this->navigationSource) {
            return $where;
        }

        if ($where->hasStatement()) {
            $where->pushStatement('AND');
        }

        $key = $this->resolveExpansionKey();
        $fieldExpression = $this->propertyToExpression($key->getProperty());
        $where->pushExpression($fieldExpression);
        $where->pushStatement('=?');
        $where->pushParameter($key->getPrimitive()->toMixed());

        return $where;
    }

    /**
     * Get the query string representing the query result
     * @return SQLExpression Query expression
     */
    public function getResultExpression(): SQLExpression
    {
        $expression = $this->getSQLExpression();
        $expression->pushStatement('SELECT');

        $columns = $this->getColumnsToQuery();

        while ($columns) {
            $column = array_shift($columns);
            $expression->pushExpression($column);

            if ($columns) {
                $expression->pushComma();
            }
        }

        $expression->pushStatement(sprintf("FROM %s", $this->quoteSingleIdentifier($this->getTable())));

        $where = $this->generateWhere();

        if ($where->hasStatement()) {
            $expression->pushStatement('WHERE');
            $expression->pushExpression($where);
        }

        $orderby = $this->generateOrderBy();

        if ($orderby->hasStatement()) {
            $expression->pushStatement('ORDER BY');
            $expression->pushExpression($orderby);
        }

        $skip = $this->getSkip();
        $top = $this->getTop();

        if ($skip->hasValue()) {
            $offset = $skip->getValue();
            $limit = $top->hasValue() ? $top->getValue() : PHP_INT_MAX;

            switch ($this->getDriver()) {
                case self::SQLServer:
                    if (!$orderby->hasStatement()) {
                        $expression->pushStatement('ORDER BY (SELECT 0)');
                    }

                    $expression->pushStatement('OFFSET ? ROWS FETCH NEXT ? ROWS ONLY');
                    $expression->pushParameters([$offset, $limit]);
                    break;

                default:
                    $expression->pushStatement('LIMIT ? OFFSET ?');
                    $expression->pushParameters([$limit, $offset]);
                    break;
            }
        }

        return $expression;
    }

    /**
     * Determine the list of columns to include in the query result
     * @return SQLExpression[]
     */
    protected function getColumnsToQuery(): array
    {
        $properties = $this->getSelectedProperties();

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
                    $expression = $this->getSQLExpression();
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

            $expression = $this->getSQLExpression();
            $expression->pushStatement($this->quoteSingleIdentifier($this->getPropertySourceName($declaredProperty)));
            $expression->pushParameter($propertyValues[$declaredProperty->getName()]->getPrimitive()->toMixed());
            $expressions[] = $expression;
        }

        if ($this->navigationSource) {
            /** @var NavigationProperty $navigationProperty */
            $navigationProperty = $this->navigationSource->getProperty();

            /** @var ReferentialConstraint $constraint */
            foreach ($navigationProperty->getConstraints() as $constraint) {
                $referencedProperty = $constraint->getReferencedProperty();
                $expression = $this->getSQLExpression();
                $expression->pushStatement($this->quoteSingleIdentifier($this->getPropertySourceName($referencedProperty)));
                $expression->pushParameter($this->navigationSource->getParent()->getEntityId()->getPrimitive()->toMixed());
                $expressions[] = $expression;
            }
        }

        if (!$expressions) {
            throw new BadRequestException(
                'missing_fields',
                'The supplied object had no fields'
            );
        }

        $expression = $this->getSQLExpression();
        $expression->pushStatement(sprintf("INSERT INTO %s", $this->quoteSingleIdentifier($this->getTable())));
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
        $this->read($key);

        $expressions = [];

        foreach ($propertyValues->getDeclaredPropertyValues() as $propertyValue) {
            $expression = $this->getSQLExpression();
            $expression->pushStatement(
                sprintf(
                    '%s=?',
                    $this->quoteSingleIdentifier($this->getPropertySourceName($propertyValue->getProperty()))
                )
            );
            $expression->pushParameter($propertyValue->getPrimitive()->toMixed());
            $expressions[] = $expression;
        }

        if ($this->navigationSource) {
            /** @var NavigationProperty $navigationProperty */
            $navigationProperty = $this->navigationSource->getProperty();

            /** @var ReferentialConstraint $constraint */
            foreach ($navigationProperty->getConstraints() as $constraint) {
                $referencedProperty = $constraint->getReferencedProperty();
                $expression = $this->getSQLExpression();
                $expression->pushStatement(
                    sprintf(
                        '%s=?',
                        $this->quoteSingleIdentifier($this->getPropertySourceName($referencedProperty))
                    )
                );
                $expression->pushParameter($this->navigationSource->getParent()->getEntityId()->getPrimitive()->toMixed());
                $expressions[] = $expression;
            }
        }

        if ($expressions) {
            $expression = $this->getSQLExpression();
            $expression->pushStatement(sprintf('UPDATE %s SET', $this->quoteSingleIdentifier($this->getTable())));

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
            $expression->pushParameter($key->getPrimitive()->toMixed());

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

        $expression = $this->getSQLExpression();
        $expression->pushStatement(
            sprintf(
                "DELETE FROM %s WHERE %s=?",
                $this->quoteSingleIdentifier($this->getTable()),
                $this->quoteSingleIdentifier($this->getPropertySourceName($type->getKey()))
            )
        );
        $expression->pushParameter($key->getPrimitive()->toMixed());

        $this->pdoModify($expression);
    }

    /**
     * Coerce types for the given record
     * @link https://www.php.net/manual/en/migration81.incompatible.php
     * @param  array  $record  Database record
     * @param  mixed  $entityId  Entity ID
     * @return Entity Entity
     */
    protected function toEntity(array $record, $entityId = null): Entity
    {
        foreach ($this->getCompute()->getProperties() as $computedProperty) {
            $key = $computedProperty->getName();
            $value = $record[$key];

            if (is_string($value) && is_numeric($value)) {
                $value = JSON::decode($value);
            }

            $record[$key] = $value;
        }

        return parent::toEntity($record, $entityId);
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
