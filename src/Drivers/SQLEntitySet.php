<?php

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Expression\Event;
use Flat3\Lodata\Expression\Event\ArgumentSeparator;
use Flat3\Lodata\Expression\Event\EndFunction;
use Flat3\Lodata\Expression\Event\EndGroup;
use Flat3\Lodata\Expression\Event\Field;
use Flat3\Lodata\Expression\Event\Literal;
use Flat3\Lodata\Expression\Event\Operator;
use Flat3\Lodata\Expression\Event\StartGroup;
use Flat3\Lodata\Expression\Node\Literal\Boolean;
use Flat3\Lodata\Expression\Node\Literal\Date;
use Flat3\Lodata\Expression\Node\Literal\DateTimeOffset;
use Flat3\Lodata\Expression\Node\Literal\TimeOfDay;
use Flat3\Lodata\Expression\Node\Operator\Arithmetic\Add;
use Flat3\Lodata\Expression\Node\Operator\Arithmetic\Div;
use Flat3\Lodata\Expression\Node\Operator\Arithmetic\DivBy;
use Flat3\Lodata\Expression\Node\Operator\Arithmetic\Mod;
use Flat3\Lodata\Expression\Node\Operator\Arithmetic\Mul;
use Flat3\Lodata\Expression\Node\Operator\Arithmetic\Sub;
use Flat3\Lodata\Expression\Node\Operator\Comparison\And_;
use Flat3\Lodata\Expression\Node\Operator\Comparison\Not_;
use Flat3\Lodata\Expression\Node\Operator\Comparison\Or_;
use Flat3\Lodata\Expression\Node\Operator\Logical\Equal;
use Flat3\Lodata\Expression\Node\Operator\Logical\GreaterThan;
use Flat3\Lodata\Expression\Node\Operator\Logical\GreaterThanOrEqual;
use Flat3\Lodata\Expression\Node\Operator\Logical\In;
use Flat3\Lodata\Expression\Node\Operator\Logical\LessThan;
use Flat3\Lodata\Expression\Node\Operator\Logical\LessThanOrEqual;
use Flat3\Lodata\Expression\Node\Operator\Logical\NotEqual;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Interfaces\CreateInterface;
use Flat3\Lodata\Interfaces\DeleteInterface;
use Flat3\Lodata\Interfaces\QueryInterface;
use Flat3\Lodata\Interfaces\QueryOptions\CountInterface;
use Flat3\Lodata\Interfaces\QueryOptions\ExpandInterface;
use Flat3\Lodata\Interfaces\QueryOptions\FilterInterface;
use Flat3\Lodata\Interfaces\QueryOptions\OrderByInterface;
use Flat3\Lodata\Interfaces\QueryOptions\PaginationInterface;
use Flat3\Lodata\Interfaces\QueryOptions\SearchInterface;
use Flat3\Lodata\Interfaces\ReadInterface;
use Flat3\Lodata\Interfaces\UpdateInterface;
use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\Type\Property;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use PDOStatement;

class SQLEntitySet extends EntitySet implements SearchInterface, FilterInterface, CountInterface, OrderByInterface, PaginationInterface, ExpandInterface, QueryInterface, ReadInterface, CreateInterface, UpdateInterface, DeleteInterface
{
    /** @var string[] $parameters */
    protected $parameters = [];

    /** @var string $where */
    protected $where = '';

    /** @var ObjectArray $sourceMap Mapping of OData properties to source identifiers */
    protected $sourceMap;

    /** @var string $table */
    private $table;

    public function __construct(string $name, EntityType $entityType)
    {
        parent::__construct($name, $entityType);
        $this->sourceMap = new ObjectArray();
    }

    public function getTable(): string
    {
        return $this->table ?: $this->identifier->getName();
    }

    public function setTable(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function getDbHandle(): PDO
    {
        $dbh = DB::connection()->getPdo();
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $dbh;
    }

    public function search(Event $event): ?bool
    {
        switch (true) {
            case $event instanceof StartGroup:
                $this->addWhere('(');

                return true;

            case $event instanceof EndGroup:
                $this->addWhere(')');

                return true;

            case $event instanceof Operator:
                $node = $event->getNode();

                switch (true) {
                    case $node instanceof Or_:
                        $this->addWhere('OR');

                        return true;

                    case $node instanceof And_:
                        $this->addWhere('AND');

                        return true;

                    case $node instanceof Not_:
                        $this->addWhere('NOT');

                        return true;
                }
                break;

            case $event instanceof Literal:
                $properties = [];

                /** @var Property $property */
                foreach ($this->getType()->getDeclaredProperties() as $property) {
                    if (!$property->isSearchable()) {
                        continue;
                    }

                    $properties[] = $property;
                }

                $properties = array_map(function ($property) use ($event) {
                    $this->addParameter('%'.$event->getValue().'%');

                    return $this->propertyToField($property).' LIKE ?';
                }, $properties);

                $this->addWhere('( '.implode(' OR ', $properties).' )');

                return true;
        }

        return false;
    }

    protected function addWhere(string $where): void
    {
        $this->where .= ' '.$where;
    }

    /**
     * Add a parameter
     *
     * @param $parameter
     */
    protected function addParameter($parameter): void
    {
        $this->parameters[] = $parameter;
    }

    protected function propertyToField(Property $property): string
    {
        return sprintf('%s.`%s`', $this->getTable(), $this->getPropertySourceName($property));
    }

    public function getPropertySourceName(Property $property): string
    {
        return $this->sourceMap[$property] ?? $property->getName();
    }

    public function read(PrimitiveType $key): ?Entity
    {
        $this->resetParameters();
        $columns = $this->getColumnsToQuery();
        $query = sprintf(
            'SELECT %s FROM %s WHERE %s=?',
            $columns,
            $this->getTable(),
            $this->propertyToField($key->getProperty())
        );
        $this->addParameter($key->get());
        $stmt = $this->pdoSelect($query);
        $this->bindParameters($stmt);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (false === $result) {
            return null;
        }

        return $this->assocToEntity($result);
    }

    public function filter(Event $event): ?bool
    {
        switch (true) {
            case $event instanceof ArgumentSeparator:
                $this->addWhere(',');

                return true;

            case $event instanceof EndGroup:
            case $event instanceof EndFunction:
                $this->addWhere(')');

                return true;

            case $event instanceof Field:
                $property = $this->getType()->getProperty($event->getValue());

                if (!$property->isFilterable()) {
                    throw new BadRequestException(
                        sprintf('The provided property (%s) is not filterable', $property->getName())
                    );
                }

                $column = $this->propertyToField($property);

                $this->addWhere($column);

                return true;

            case $event instanceof Literal:
                $this->addWhere('?');

                $node = $event->getNode();

                switch (true) {
                    case $node instanceof Boolean:
                        $this->addParameter(null === $event->getValue() ? null : (int) $event->getValue());
                        break;

                    case $node instanceof Date:
                        $this->addParameter($node->getValue()->format('Y-m-d 00:00:00'));
                        break;

                    case $node instanceof DateTimeOffset:
                        $this->addParameter($node->getValue()->format('Y-m-d H:i:s'));
                        break;

                    case $node instanceof TimeOfDay:
                        $this->addParameter($node->getValue()->format('H:i:s'));
                        break;

                    default:
                        $this->addParameter($event->getValue());
                        break;
                }

                return true;

            case $event instanceof Operator:
                $operator = $event->getNode();

                switch (true) {
                    case $operator instanceof Add:
                        $this->addWhere('+');

                        return true;

                    case $operator instanceof Div:
                        $this->addWhere('DIV');

                        return true;

                    case $operator instanceof DivBy:
                        $this->addWhere('/');

                        return true;

                    case $operator instanceof Mod:
                        $this->addWhere('%');

                        return true;

                    case $operator instanceof Mul:
                        $this->addWhere('*');

                        return true;

                    case $operator instanceof Sub:
                        $this->addWhere('-');

                        return true;

                    case $operator instanceof And_:
                        $this->addWhere('AND');

                        return true;

                    case $operator instanceof Not_:
                        $this->addWhere('NOT');

                        return true;

                    case $operator instanceof Or_:
                        $this->addWhere('OR');

                        return true;

                    case $operator instanceof Equal:
                        $this->addWhere('=');

                        return true;

                    case $operator instanceof GreaterThan:
                        $this->addWhere('>');

                        return true;

                    case $operator instanceof GreaterThanOrEqual:
                        $this->addWhere('>=');

                        return true;

                    case $operator instanceof In:
                        $this->addWhere('IN');

                        return true;

                    case $operator instanceof LessThan:
                        $this->addWhere('<');

                        return true;

                    case $operator instanceof LessThanOrEqual:
                        $this->addWhere('<=');

                        return true;

                    case $operator instanceof NotEqual:
                        $this->addWhere('!=');

                        return true;
                }
                break;

            case $event instanceof StartGroup:
                $this->addWhere('(');

                return true;
        }

        return false;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function count(): int
    {
        $this->resetParameters();

        $query = $this->pdoSelect($this->getRowCountQueryString());
        return $query->fetchColumn();
    }

    protected function resetParameters(): void
    {
        $this->parameters = [];
    }

    private function pdoModify(string $query_string): ?int
    {
        $dbh = $this->getDbHandle();

        try {
            $stmt = $dbh->prepare($query_string);
            $this->bindParameters($stmt);
            $stmt->execute();
        } catch (PDOException $e) {
            throw new InternalServerErrorException('query_error',
                sprintf('The executed query returned an error: %s', $e->getMessage()));
        }

        return $dbh->lastInsertId();
    }

    private function pdoSelect(string $query_string): PDOStatement
    {
        $dbh = $this->getDbHandle();

        try {
            $stmt = $dbh->prepare($query_string);
            $this->bindParameters($stmt);
            $stmt->execute();
        } catch (PDOException $e) {
            throw new InternalServerErrorException('query_error',
                sprintf('The executed query returned an error: %s', $e->getMessage()));
        }

        return $stmt;
    }

    protected function bindParameters(PDOStatement $stmt)
    {
        $parameters = $this->parameters;
        if (!$parameters) {
            return;
        }

        foreach ($this->parameters as $position => $value) {
            $stmt->bindValue($position + 1, $value);
        }
    }

    /**
     * Get a version of the query string that counts the total number of rows in the collection
     *
     * @return string
     */
    public function getRowCountQueryString(): string
    {
        $this->resetParameters();
        $queryString = sprintf('SELECT COUNT(*) FROM %s', $this->getTable());

        $this->generateWhere();

        if ($this->where) {
            $queryString .= sprintf(' WHERE%s', $this->where);
        }

        return $queryString;
    }

    protected function generateWhere(): void
    {
        $this->where = '';

        if ($this->key) {
            $this->addWhere($this->propertyToField($this->key->getProperty()).' = ?');
            $this->addParameter($this->key->get());
            return;
        }

        $filter = $this->transaction->getFilter();
        if ($filter->hasValue()) {
            $this->whereMaybeAnd();
            $validLiterals = [];

            /** @var Property $property */
            foreach ($this->getType()->getDeclaredProperties() as $property) {
                if ($property->isFilterable()) {
                    $validLiterals[] = (string) $property->getName();
                }
            }

            $filter->applyQuery($this, $validLiterals);
        }

        $search = $this->transaction->getSearch();
        if ($search->hasValue()) {
            if (!$this->getType()->getDeclaredProperties()->filter(function ($property) {
                return $property->isSearchable();
            })->hasEntries()) {
                throw new InternalServerErrorException(
                    'query_no_searchable_properties',
                    'The provided query had no properties marked searchable'
                );
            }

            $this->whereMaybeAnd();
            $search->applyQuery($this);
        }
    }

    protected function whereMaybeAnd(): void
    {
        if ($this->where) {
            $this->addWhere('AND');
        }
    }

    public function assocToEntity(array $row): Entity
    {
        $entity = $this->newEntity();

        $key = $this->getType()->getKey()->getName();
        $entity->setPrimitive($key, $row[$key]);

        foreach ($row as $id => $value) {
            $entity[$id] = $value;
        }

        return $entity;
    }

    public function query(): array
    {
        $stmt = $this->pdoSelect($this->getSetResultQueryString());

        $results = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = $this->assocToEntity($row);
        }

        return $results;
    }

    public function getSetResultQueryString(): string
    {
        $this->resetParameters();
        $columns = $this->getColumnsToQuery();

        $query = sprintf('SELECT %s FROM %s', $columns, $this->getTable());

        $this->generateWhere();

        if ($this->where) {
            $query .= sprintf(' WHERE%s', $this->where);
        }

        $orderby = $this->transaction->getOrderBy();

        if ($orderby->hasValue()) {
            $ob = implode(', ', array_map(function ($o) {
                [$literal, $direction] = $o;

                return "$literal $direction";
            }, $orderby->getSortOrders($this)));

            $query .= ' ORDER BY '.$ob;
        }

        $query .= $this->generateLimits();

        return $query;
    }

    protected function getColumnsToQuery(): string
    {
        $select = $this->transaction->getSelect();

        $properties = $select->getSelectedProperties($this);

        $key = $this->getType()->getKey();

        if (!$properties[$key]) {
            $properties[] = $key;
        }

        return $this->propertiesToColumns($properties);
    }

    protected function propertiesToColumns(ObjectArray $properties): string
    {
        $columns = [];

        foreach ($properties as $property) {
            $columns[] = $this->propertyToColumn($property);
        }

        $columns = implode(', ', $columns);

        if (!$columns) {
            throw new InternalServerErrorException(
                'empty_property_set',
                'There are no properties to return in this query'
            );
        }

        return $columns;
    }

    /**
     * Apply casts based on property type
     *
     * @param  Property  $property
     *
     * @return string
     */
    protected function propertyToColumn(Property $property): string
    {
        $column = $this->propertyToField($property);

        return sprintf('%s AS %s', $column, $property->getName());
    }

    public function generateLimits(): string
    {
        $limits = '';

        if ($this->top === PHP_INT_MAX) {
            return $limits;
        }

        $limits .= ' LIMIT ?';
        $this->addParameter($this->top);

        if (!$this->skip) {
            return $limits;
        }

        $limits .= ' OFFSET ?';
        $this->addParameter($this->skip);

        return $limits;
    }

    public function create(): Entity
    {
        $entity = $this->newEntity();
        $entity->fromArray($this->transaction->getBody());

        $type = $this->getType();
        $properties = $type->getDeclaredProperties()->pick($entity->getPrimitives()->keys());
        $primitives = $entity->getPrimitives();

        $fields = [];

        /** @var Property $property */
        foreach ($properties as $property) {
            $fields[] = $this->getPropertySourceName($property);
            $this->addParameter($primitives->get($property->getName())->get());
        }

        $fieldsList = implode(',', $fields);
        $valuesList = implode(',', array_fill(0, count($fields), '?'));

        $query = sprintf('INSERT INTO %s (%s) VALUES (%s)', $this->getTable(), $fieldsList, $valuesList);
        $id = $this->pdoModify($query);
        if ($id) {
            $entity->setEntityId($id);
        }

        $key = $entity->getEntityId();
        $key->set($id);

        return $this->read($key);
    }

    public function update(PrimitiveType $key): Entity
    {
        $this->resetParameters();
        $entity = $this->newEntity();
        $entity->fromArray($this->transaction->getBody());

        $type = $this->getType();
        $properties = $type->getDeclaredProperties()->pick($entity->getPrimitives()->keys());
        $primitives = $entity->getPrimitives();

        $fields = [];

        /** @var Property $property */
        foreach ($properties as $property) {
            $fields[] = sprintf('%s=?', $this->getPropertySourceName($property));
            $this->addParameter($primitives->get($property->getName())->get());
        }
        $fields = implode(',', $fields);

        $this->addParameter($key->get());

        $query = sprintf(
            'UPDATE %s SET %s WHERE %s=?',
            $this->getTable(),
            $fields,
            $this->propertyToField($type->getKey())
        );

        $this->pdoModify($query);

        return $this->read($key);
    }

    public function delete(PrimitiveType $key)
    {
        $this->resetParameters();
        $type = $this->getType();

        $this->addParameter($key->get());

        $query = sprintf(
            'DELETE FROM %s WHERE %s=?',
            $this->getTable(),
            $this->getPropertySourceName($type->getKey())
        );

        $this->pdoModify($query);
    }
}
