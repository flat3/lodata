<?php

namespace Flat3\Lodata\Drivers;

use Carbon\Carbon;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Expression\Event;
use Flat3\Lodata\Expression\Node;
use Flat3\Lodata\Expression\Node\Func\Arithmetic\Ceiling;
use Flat3\Lodata\Expression\Node\Func\Arithmetic\Floor;
use Flat3\Lodata\Expression\Node\Func\Arithmetic\Round;
use Flat3\Lodata\Expression\Node\Func\DateTime\Date;
use Flat3\Lodata\Expression\Node\Func\DateTime\Day;
use Flat3\Lodata\Expression\Node\Func\DateTime\Hour;
use Flat3\Lodata\Expression\Node\Func\DateTime\Minute;
use Flat3\Lodata\Expression\Node\Func\DateTime\Month;
use Flat3\Lodata\Expression\Node\Func\DateTime\Second;
use Flat3\Lodata\Expression\Node\Func\DateTime\Time;
use Flat3\Lodata\Expression\Node\Func\DateTime\Year;
use Flat3\Lodata\Expression\Node\Func\String\MatchesPattern;
use Flat3\Lodata\Expression\Node\Func\String\ToLower;
use Flat3\Lodata\Expression\Node\Func\String\ToUpper;
use Flat3\Lodata\Expression\Node\Func\String\Trim;
use Flat3\Lodata\Expression\Node\Func\StringCollection\Concat;
use Flat3\Lodata\Expression\Node\Func\StringCollection\Contains;
use Flat3\Lodata\Expression\Node\Func\StringCollection\EndsWith;
use Flat3\Lodata\Expression\Node\Func\StringCollection\IndexOf;
use Flat3\Lodata\Expression\Node\Func\StringCollection\Length;
use Flat3\Lodata\Expression\Node\Func\StringCollection\StartsWith;
use Flat3\Lodata\Expression\Node\Func\StringCollection\Substring;
use Flat3\Lodata\Expression\Node\Literal;
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
use Flat3\Lodata\Expression\Node\Property;
use Flat3\Lodata\Expression\Parser\Filter as FilterParser;
use Flat3\Lodata\Expression\Parser\Search as SearchParser;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\EntitySet\CountInterface;
use Flat3\Lodata\Interfaces\EntitySet\CreateInterface;
use Flat3\Lodata\Interfaces\EntitySet\DeleteInterface;
use Flat3\Lodata\Interfaces\EntitySet\FilterInterface;
use Flat3\Lodata\Interfaces\EntitySet\OrderByInterface;
use Flat3\Lodata\Interfaces\EntitySet\PaginationInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\EntitySet\ReadInterface;
use Flat3\Lodata\Interfaces\EntitySet\SearchInterface;
use Flat3\Lodata\Interfaces\EntitySet\UpdateInterface;
use Flat3\Lodata\Type\TimeOfDay;
use Generator;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Class CollectionEntitySet
 * @package Flat3\Lodata\Drivers
 */
class CollectionEntitySet extends EntitySet implements CountInterface, CreateInterface, DeleteInterface, QueryInterface, ReadInterface, UpdateInterface, PaginationInterface, OrderByInterface, SearchInterface, FilterInterface
{
    /** @var Collection $collection */
    protected $collection;

    /**
     * Set the collection used by this entity set
     * @param  Collection  $collection  Collection
     * @return $this
     */
    public function setCollection(Collection $collection): self
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Get the collection used by this entity set
     * @return Collection Collection
     */
    public function getCollection(): Collection
    {
        return $this->collection;
    }

    public function count(): int
    {
        return $this->collection->count();
    }

    public function query(): Generator
    {
        $collection = $this->collection;

        if ($this->getFilter()->hasValue()) {
            $parser = new FilterParser($this->getTransaction());
            $parser->pushEntitySet($this);

            $tree = $parser->generateTree($this->getFilter()->getValue());

            $collection = $collection->filter(function ($item) use ($tree) {
                $eval = function (Node $node) use (&$eval, $item) {
                    $left = !$node->getLeftNode() ?: $eval($node->getLeftNode());
                    $right = !$node->getRightNode() ?: $eval($node->getRightNode());
                    $args = array_map($eval, $node->getArguments());

                    /** @var Carbon|null $carbon */
                    $carbon = null;

                    // String functions
                    switch (true) {
                        case $node instanceof ToLower:
                        case $node instanceof ToUpper:
                        case $node instanceof Trim:
                        case $node instanceof MatchesPattern:
                            if (!in_array(gettype($args[0]), ['int', 'string', 'float'])) {
                                return false;
                            }

                            $args[0] = (string) $args[0];
                            break;
                    }

                    // String and collection functions
                    switch (true) {
                        case $node instanceof StartsWith:
                        case $node instanceof EndsWith:
                        case $node instanceof Substring:
                        case $node instanceof Contains:
                        case $node instanceof Concat:
                        case $node instanceof Length:
                        case $node instanceof IndexOf:
                            if (!in_array(gettype($args[0]), ['int', 'string', 'float'])) {
                                return false;
                            }

                            $args[0] = (string) $args[0];
                            break;
                    }

                    // Arithmetic functions
                    switch (true) {
                        case $node instanceof Round:
                        case $node instanceof Ceiling:
                        case $node instanceof Floor:
                            if (!is_numeric($args[0])) {
                                return 0;
                            }

                            break;
                    }

                    // Arithmetic operators
                    switch (true) {
                        case $node instanceof Add:
                        case $node instanceof Sub:
                        case $node instanceof Div:
                        case $node instanceof DivBy:
                        case $node instanceof Mul:
                            $left = +$left;
                            $right = +$right;
                            break;
                    }

                    // Datetime functions
                    switch (true) {
                        case $node instanceof Day:
                        case $node instanceof Date:
                        case $node instanceof Hour:
                        case $node instanceof Minute:
                        case $node instanceof Month:
                        case $node instanceof Second:
                        case $node instanceof Time:
                        case $node instanceof Year:
                            $carbon = new Carbon($args[0]);
                            break;
                    }

                    switch (true) {
                        case $node instanceof Equal:
                            return $left == $right;

                        case $node instanceof NotEqual:
                            return $left != $right;

                        case $node instanceof GreaterThan:
                            return $left > $right;

                        case $node instanceof GreaterThanOrEqual:
                            return $left >= $right;

                        case $node instanceof LessThan:
                            return $left < $right;

                        case $node instanceof LessThanOrEqual:
                            return $left <= $right;

                        case $node instanceof In:
                            return in_array($left, $args);

                        case $node instanceof Or_:
                            return $left || $right;

                        case $node instanceof And_:
                            return $left && $right;

                        case $node instanceof Not_:
                            return !$left;

                        case $node instanceof StartsWith:
                            return Str::startsWith(...$args);

                        case $node instanceof Substring:
                            return substr(...$args);

                        case $node instanceof EndsWith:
                            return Str::endsWith(...$args);

                        case $node instanceof Contains:
                            return Str::contains(...$args);

                        case $node instanceof Concat:
                            return join('', $args);

                        case $node instanceof Length:
                            return Str::length(...$args);

                        case $node instanceof IndexOf:
                            return strpos(...$args);

                        case $node instanceof Property:
                            return $item[$node->getValue()] ?? null;

                        case $node instanceof Literal:
                            return $node->getValue();

                        case $node instanceof Round:
                            return round(...$args);

                        case $node instanceof Ceiling:
                            return ceil(...$args);

                        case $node instanceof Floor:
                            return floor(...$args);

                        case $node instanceof ToLower:
                            return strtolower(...$args);

                        case $node instanceof ToUpper:
                            return strtoupper(...$args);

                        case $node instanceof Trim:
                            return trim(...$args);

                        case $node instanceof MatchesPattern:
                            return 1 === preg_match('/'.$args[1].'/', $args[0]);

                        case $node instanceof Add:
                            return $left + $right;

                        case $node instanceof Sub:
                            return $left - $right;

                        case $node instanceof Div:
                            return $left / $right;

                        case $node instanceof DivBy:
                            return (float) $left / (float) $right;

                        case $node instanceof Mul:
                            return $left * $right;

                        case $node instanceof Mod:
                            return $left % $right;

                        case $node instanceof Day:
                            return $carbon->day;

                        case $node instanceof Date:
                            return $carbon->format(\Flat3\Lodata\Type\Date::DATE_FORMAT);

                        case $node instanceof Hour:
                            return $carbon->hour;

                        case $node instanceof Minute:
                            return $carbon->minute;

                        case $node instanceof Month:
                            return $carbon->month;

                        case $node instanceof Second:
                            return $carbon->second;

                        case $node instanceof Time:
                            return $carbon->format(TimeOfDay::DATE_FORMAT);

                        case $node instanceof Year:
                            return $carbon->year;
                    }

                    throw new NotImplementedException();
                };

                return !!$eval($tree);
            });
        }

        if ($this->getSearch()->hasValue()) {
            $search = $this->getSearch();

            $parser = new SearchParser();
            $parser->pushEntitySet($this);

            $tree = $parser->generateTree($search->getValue());

            $collection = $collection->filter(function ($item) use ($tree) {
                $eval = function (Node $node) use (&$eval, $item) {
                    $left = !$node->getLeftNode() ?: $eval($node->getLeftNode());
                    $right = !$node->getRightNode() ?: $eval($node->getRightNode());

                    switch (true) {
                        case $node instanceof Literal\String_:
                            /** @var DeclaredProperty[] $props */
                            $props = $this->getType()->getDeclaredProperties()->filter(function ($property) {
                                return $property->isSearchable();
                            });

                            foreach ($props as $prop) {
                                if (Str::contains($item[$prop->getName()], $node->getValue())) {
                                    return true;
                                }
                            }

                            return false;

                        case $node instanceof And_:
                            return $left && $right;

                        case $node instanceof Or_:
                            return $left || $right;

                        case $node instanceof Not_:
                            return !$left;
                    }

                    throw new NotImplementedException();
                };

                return !!$eval($tree);
            });
        }

        if ($this->getOrderBy()->hasValue()) {
            $orders = $this->getOrderBy()->getSortOrders();

            $collection = $collection->map(function ($item, $key) {
                return array_merge(['__id' => $key], $item);
            });

            if (version_compare(Application::VERSION, '8', '<')) {
                if (count($orders) > 1) {
                    throw new NotImplementedException(
                        'no_multiple_orderby',
                        'This version of Laravel does not support multiple sort criteria'
                    );
                }

                $orders = Arr::first($orders);

                if ($orders[1] === 'asc') {
                    $collection = $collection->sortBy($orders[0]);
                } else {
                    $collection = $collection->sortByDesc($orders[0]);
                }
            } else {
                $collection = $collection->sortBy($orders);
            }

            $collection = $collection->keyBy('__id');
        }

        if ($this->getSkip()->hasValue()) {
            $collection = $collection->skip($this->getSkip()->getValue());
        }

        if ($this->getTop()->hasValue()) {
            $collection = $collection->slice(0, $this->getTop()->getValue());
        }

        foreach ($collection->all() as $key => $item) {
            $entity = $this->newEntity();
            $entity->setEntityId($key);
            $entity->fromArray($item);

            yield $entity;
        }
    }

    public function create(): Entity
    {
        $entity = $this->newEntity();
        $body = $this->transaction->getBody();
        $entity->fromArray($body);
        $entityId = $entity->getEntityId();

        if ($entityId) {
            $key = $entityId->getPrimitiveValue()->get();
            $this->collection[$key] = $entity->toArray();
        } else {
            $this->collection[] = $entity->toArray();
            $entity->setEntityId($this->collection->count() - 1);
        }

        return $entity;
    }

    public function delete(PropertyValue $key): void
    {
        $this->collection->forget($key->getPrimitiveValue()->get());
    }

    public function read(PropertyValue $key): ?Entity
    {
        $item = $this->collection->get($key->getPrimitiveValue()->get());

        if ($item === null) {
            return null;
        }

        $entity = $this->newEntity();
        $entity['id'] = $key->getPrimitiveValue()->get();
        $entity->fromArray($item);

        return $entity;
    }

    public function update(PropertyValue $key): Entity
    {
        $entity = $this->read($key);
        $body = $this->transaction->getBody();
        $entity->fromArray($body);
        $item = $entity->toArray();
        unset($item['id']);

        $this->collection[$key->getPrimitiveValue()->get()] = $item;

        return $this->read($key);
    }

    public function search(Event $event): ?bool
    {
        return true;
    }

    public function filter(Event $event): ?bool
    {
        return true;
    }
}