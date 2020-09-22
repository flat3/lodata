<?php

namespace Flat3\OData\Drivers\Database;

use Flat3\OData\Exception\BadRequestException;
use Flat3\OData\Exception\NodeHandledException;
use Flat3\OData\Exception\StoreException;
use Flat3\OData\Expression\Event;
use Flat3\OData\Expression\Event\ArgumentSeparator;
use Flat3\OData\Expression\Event\EndFunction;
use Flat3\OData\Expression\Event\EndGroup;
use Flat3\OData\Expression\Event\Field;
use Flat3\OData\Expression\Event\Literal;
use Flat3\OData\Expression\Event\Operator;
use Flat3\OData\Expression\Event\StartFunction;
use Flat3\OData\Expression\Event\StartGroup;
use Flat3\OData\Expression\Node\Func\Arithmetic\Ceiling;
use Flat3\OData\Expression\Node\Func\Arithmetic\Floor;
use Flat3\OData\Expression\Node\Func\Arithmetic\Round;
use Flat3\OData\Expression\Node\Func\DateTime\Date;
use Flat3\OData\Expression\Node\Func\DateTime\Day;
use Flat3\OData\Expression\Node\Func\DateTime\Hour;
use Flat3\OData\Expression\Node\Func\DateTime\Minute;
use Flat3\OData\Expression\Node\Func\DateTime\Month;
use Flat3\OData\Expression\Node\Func\DateTime\Now;
use Flat3\OData\Expression\Node\Func\DateTime\Second;
use Flat3\OData\Expression\Node\Func\DateTime\Time;
use Flat3\OData\Expression\Node\Func\DateTime\Year;
use Flat3\OData\Expression\Node\Func\String\MatchesPattern;
use Flat3\OData\Expression\Node\Func\String\ToLower;
use Flat3\OData\Expression\Node\Func\String\ToUpper;
use Flat3\OData\Expression\Node\Func\String\Trim;
use Flat3\OData\Expression\Node\Func\StringCollection\Concat;
use Flat3\OData\Expression\Node\Func\StringCollection\Contains;
use Flat3\OData\Expression\Node\Func\StringCollection\EndsWith;
use Flat3\OData\Expression\Node\Func\StringCollection\IndexOf;
use Flat3\OData\Expression\Node\Func\StringCollection\Length;
use Flat3\OData\Expression\Node\Func\StringCollection\StartsWith;
use Flat3\OData\Expression\Node\Func\StringCollection\Substring;
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
        return sprintf('%s.`%s`', $this->store->getTable(), $this->store->getPropertySourceName($property));
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
                $this->addParameter($event->getValue());

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

            case $event instanceof StartFunction:
                $func = $event->getNode();

                switch (true) {
                    case $func instanceof Ceiling:
                        $this->addWhere('CEILING(');

                        return true;

                    case $func instanceof Floor:
                        $this->addWhere('FLOOR(');

                        return true;

                    case $func instanceof Round:
                        $this->addWhere('ROUND(');

                        return true;

                    case $func instanceof Date:
                        $this->addWhere('DATE(');

                        return true;

                    case $func instanceof Day:
                        $this->addWhere('DAY(');

                        return true;

                    case $func instanceof Hour:
                        $this->addWhere('HOUR(');

                        return true;

                    case $func instanceof Minute:
                        $this->addWhere('MINUTE(');

                        return true;

                    case $func instanceof Month:
                        $this->addWhere('MONTH(');

                        return true;

                    case $func instanceof Now:
                        $this->addWhere('NOW(');

                        return true;

                    case $func instanceof Second:
                        $this->addWhere('SECOND(');

                        return true;

                    case $func instanceof Time:
                        $this->addWhere('TIME(');

                        return true;

                    case $func instanceof Year:
                        $this->addWhere('YEAR(');

                        return true;

                    case $func instanceof MatchesPattern:
                        $this->addWhere('REGEXP_LIKE(');

                        return true;

                    case $func instanceof ToLower:
                        $this->addWhere('LOWER(');

                        return true;

                    case $func instanceof ToUpper:
                        $this->addWhere('UPPER(');

                        return true;

                    case $func instanceof Trim:
                        $this->addWhere('TRIM(');

                        return true;

                    case $func instanceof Concat:
                        $this->addWhere('CONCAT(');

                        return true;

                    case $func instanceof Contains:
                    case $func instanceof EndsWith:
                    case $func instanceof StartsWith:
                        $arguments = $func->getArguments();
                        list($arg1, $arg2) = $arguments;

                        $arg1->compute();
                        $this->addWhere('LIKE');
                        $value = $arg2->getValue();

                        if ($func instanceof StartsWith || $func instanceof Contains) {
                            $value .= '%';
                        }

                        if ($func instanceof EndsWith || $func instanceof Contains) {
                            $value = '%'.$value;
                        }

                        $arg2->setValue($value);
                        $arg2->compute();
                        throw new NodeHandledException();

                    case $func instanceof IndexOf:
                        $this->addWhere('INSTR(');

                        return true;

                    case $func instanceof Length:
                        $this->addWhere('LENGTH(');

                        return true;

                    case $func instanceof Substring:
                        $this->addWhere('SUBSTRING(');

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
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($position + 1, $value, $type);
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
            $filter->apply_query($this, $validLiterals);
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
