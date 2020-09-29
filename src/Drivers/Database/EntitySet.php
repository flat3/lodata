<?php

namespace Flat3\OData\Drivers\Database;

use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Exception\StoreException;
use Flat3\OData\Expression\Event;
use Flat3\OData\Expression\Event\ArgumentSeparator;
use Flat3\OData\Expression\Event\EndFunction;
use Flat3\OData\Expression\Event\EndGroup;
use Flat3\OData\Expression\Event\Field;
use Flat3\OData\Expression\Event\Literal;
use Flat3\OData\Expression\Event\Operator;
use Flat3\OData\Expression\Event\StartGroup;
use Flat3\OData\Expression\Node\Literal\Boolean;
use Flat3\OData\Expression\Node\Operator\Arithmetic\Add;
use Flat3\OData\Expression\Node\Operator\Arithmetic\Div;
use Flat3\OData\Expression\Node\Operator\Arithmetic\DivBy;
use Flat3\OData\Expression\Node\Operator\Arithmetic\Mod;
use Flat3\OData\Expression\Node\Operator\Arithmetic\Mul;
use Flat3\OData\Expression\Node\Operator\Arithmetic\Sub;
use Flat3\OData\Expression\Node\Operator\Comparison\And_;
use Flat3\OData\Expression\Node\Operator\Comparison\Not_;
use Flat3\OData\Expression\Node\Operator\Comparison\Or_;
use Flat3\OData\Expression\Node\Operator\Logical\Equal;
use Flat3\OData\Expression\Node\Operator\Logical\GreaterThan;
use Flat3\OData\Expression\Node\Operator\Logical\GreaterThanOrEqual;
use Flat3\OData\Expression\Node\Operator\Logical\In;
use Flat3\OData\Expression\Node\Operator\Logical\LessThan;
use Flat3\OData\Expression\Node\Operator\Logical\LessThanOrEqual;
use Flat3\OData\Expression\Node\Operator\Logical\NotEqual;
use Flat3\OData\ObjectArray;
use Flat3\OData\Property;
use PDO;
use PDOException;
use PDOStatement;

class EntitySet extends \Flat3\OData\EntitySet
{
    /** @var string[] $parameters */
    protected $parameters = [];

    /** @var string $where */
    protected $where = '';

    /** @var Store $store */
    protected $store;

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
                foreach ($this->getDeclaredProperties() as $property) {
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
        $field = sprintf('%s.`%s`', $this->store->getTable(), $this->store->getPropertySourceName($property));

        return $field;
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
                $property = $this->getStore()->getTypeProperty($event->getValue());

                if (!$property->isFilterable()) {
                    throw new BadRequestException(
                        sprintf('The provided property (%s) is not filterable', $property->getIdentifier())
                    );
                }

                $column = $this->propertyToField($property);

                $this->addWhere($column);

                return true;

            case $event instanceof Literal:
                $this->addWhere('?');

                switch (true) {
                    case $event->getNode() instanceof Boolean:
                        $this->addParameter(null === $event->getValue() ? null : (int) $event->getValue());
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

    public function countResults(): int
    {
        $this->resetParameters();

        $query = $this->pdoQuery($this->getRowCountQueryString());
        return $query->fetchColumn();
    }

    protected function resetParameters(): void
    {
        $this->parameters = [];
    }

    private function pdoQuery(string $query_string): PDOStatement
    {
        $dbh = $this->store->getDbHandle();
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        try {
            $stmt = $dbh->prepare($query_string);
            $this->bindParameters($stmt);
            $stmt->execute();
        } catch (PDOException $e) {
            throw new StoreException(sprintf('The executed query returned an error: %s', $e->getMessage()));
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
        $queryString = sprintf('SELECT COUNT(*) FROM %s', $this->store->getTable());

        $this->generateWhere();

        if ($this->where) {
            $queryString .= sprintf(' WHERE%s', $this->where);
        }

        return $queryString;
    }

    protected function generateWhere(): void
    {
        $this->where = '';

        if ($this->entityId) {
            $this->addWhere($this->propertyToField($this->entityKey).' = ?');
            $this->addParameter($this->entityId->getInternalValue());
        }

        $filter = $this->transaction->getFilter();
        if ($filter->hasValue()) {
            $this->whereMaybeAnd();
            $validLiterals = [];

            /** @var Property $property */
            foreach ($this->getDeclaredProperties() as $property) {
                if ($property->isFilterable()) {
                    $validLiterals[] = (string) $property->getIdentifier();
                }
            }

            $filter->applyQuery($this, $validLiterals);
        }

        $search = $this->transaction->getSearch();
        if ($search->hasValue()) {
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

    protected function generateResultSet(): void
    {
        $stmt = $this->pdoQuery($this->getSetResultQueryString());
        $this->resultSet = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSetResultQueryString(): string
    {
        $this->resetParameters();
        $columns = $this->selectToColumns();

        $query = sprintf('SELECT %s FROM %s', $columns, $this->store->getTable());

        $this->generateWhere();

        if ($this->where) {
            $query .= sprintf(' WHERE%s', $this->where);
        }

        $query .= $this->generateLimits();

        $orderby = $this->transaction->getOrderBy();

        if ($orderby->hasValue()) {
            $ob = implode(', ', array_reduce($orderby->getValue(), function ($ob, $o) {
                [$literal, $direction] = $o;

                $ob[] = "$literal $direction";
            }, []));

            $query .= ' ORDER BY '.$ob;
        }

        return $query;
    }

    protected function selectToColumns(): string
    {
        $select = $this->transaction->getSelect();

        $properties = $select->getSelectedProperties($this->store);

        $key = $this->getStore()->getEntityType()->getKey();

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
            throw new StoreException('There are no properties to return in this query');
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

        return sprintf('%s AS %s', $column, $property->getIdentifier());
    }

    public function generateLimits(): string
    {
        $limits = '';

        if (!$this->top) {
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
}
