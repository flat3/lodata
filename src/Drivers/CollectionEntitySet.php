<?php

namespace Flat3\Lodata\Drivers;

use Carbon\Carbon;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Expression\Event;
use Flat3\Lodata\Expression\Node;
use Flat3\Lodata\Expression\Node\Literal;
use Flat3\Lodata\Expression\Node\Operator;
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
use Flat3\Lodata\Type;
use Generator;
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
                    $leftNode = $node->getLeftNode();
                    $rightNode = $node->getRightNode();

                    $left = !$leftNode ?: $eval($leftNode);
                    $right = !$rightNode ?: $eval($rightNode);

                    $args = array_map($eval, $node->getArguments());

                    /** @var Carbon|null $carbon */
                    $carbon = null;

                    if ($left instanceof Carbon and !$right instanceof Carbon) {
                        $right = new Carbon($right);
                    }

                    if ($right instanceof Carbon and !$left instanceof Carbon) {
                        $left = new Carbon($left);
                    }

                    // String functions
                    switch (true) {
                        case $node instanceof Node\Func\String\ToLower:
                        case $node instanceof Node\Func\String\ToUpper:
                        case $node instanceof Node\Func\String\Trim:
                        case $node instanceof Node\Func\String\MatchesPattern:
                            if (!in_array(gettype($args[0]), ['int', 'string', 'float'])) {
                                return false;
                            }

                            $args[0] = (string) $args[0];
                            break;
                    }

                    // String and collection functions
                    switch (true) {
                        case $node instanceof Node\Func\StringCollection\StartsWith:
                        case $node instanceof Node\Func\StringCollection\EndsWith:
                        case $node instanceof Node\Func\StringCollection\Substring:
                        case $node instanceof Node\Func\StringCollection\Contains:
                        case $node instanceof Node\Func\StringCollection\Concat:
                        case $node instanceof Node\Func\StringCollection\Length:
                        case $node instanceof Node\Func\StringCollection\IndexOf:
                            if (!in_array(gettype($args[0]), ['int', 'string', 'float'])) {
                                return false;
                            }

                            $args[0] = (string) $args[0];
                            break;
                    }

                    // Arithmetic functions
                    switch (true) {
                        case $node instanceof Node\Func\Arithmetic\Round:
                        case $node instanceof Node\Func\Arithmetic\Ceiling:
                        case $node instanceof Node\Func\Arithmetic\Floor:
                            if (!is_numeric($args[0])) {
                                return 0;
                            }

                            break;
                    }

                    // Arithmetic operators
                    switch (true) {
                        case $node instanceof Operator\Arithmetic\Add:
                        case $node instanceof Operator\Arithmetic\Sub:
                        case $node instanceof Operator\Arithmetic\Div:
                        case $node instanceof Operator\Arithmetic\DivBy:
                        case $node instanceof Operator\Arithmetic\Mul:
                            $left = +$left;
                            $right = +$right;
                            break;
                    }

                    // Datetime functions
                    switch (true) {
                        case $node instanceof Node\Func\DateTime\Day:
                        case $node instanceof Node\Func\DateTime\Date:
                        case $node instanceof Node\Func\DateTime\Hour:
                        case $node instanceof Node\Func\DateTime\Minute:
                        case $node instanceof Node\Func\DateTime\Month:
                        case $node instanceof Node\Func\DateTime\Second:
                        case $node instanceof Node\Func\DateTime\Time:
                        case $node instanceof Node\Func\DateTime\Year:
                            $carbon = new Carbon($args[0]);
                            break;
                    }

                    // Type conversion
                    if ($leftNode instanceof Type\Decimal && $leftNode->get() === NAN) {
                        $left = NAN;
                    }

                    if ($rightNode instanceof Type\Decimal && $rightNode->get() === NAN) {
                        $right = NAN;
                    }

                    if ($leftNode instanceof Literal\TimeOfDay || $leftNode instanceof Node\Func\DateTime\Time || $leftNode instanceof Type\TimeOfDay) {
                        $left = $left->toTimeString('microsecond');
                    }

                    if ($rightNode instanceof Literal\TimeOfDay || $rightNode instanceof Node\Func\DateTime\Time || $rightNode instanceof Type\TimeOfDay) {
                        $right = $right->toTimeString('microsecond');
                    }

                    if ($leftNode instanceof Node\Func\DateTime\Date || $leftNode instanceof Literal\Date || $leftNode instanceof Type\Date) {
                        $left = $left->toDateString();
                    }

                    if ($rightNode instanceof Node\Func\DateTime\Date || $rightNode instanceof Literal\Date || $rightNode instanceof Type\Date) {
                        $right = $right->toDateString();
                    }

                    switch (true) {
                        case $node instanceof Operator\Logical\Equal:
                            return $left == $right;

                        case $node instanceof Operator\Logical\NotEqual:
                            return $left != $right;

                        case $node instanceof Operator\Logical\GreaterThan:
                            return $left > $right;

                        case $node instanceof Operator\Logical\GreaterThanOrEqual:
                            return $left >= $right;

                        case $node instanceof Operator\Logical\LessThan:
                            return $left < $right;

                        case $node instanceof Operator\Logical\LessThanOrEqual:
                            return $left <= $right;

                        case $node instanceof Operator\Logical\In:
                            return in_array($left, $args);

                        case $node instanceof Operator\Comparison\Or_:
                            return $left || $right;

                        case $node instanceof Operator\Comparison\And_:
                            return $left && $right;

                        case $node instanceof Operator\Comparison\Not_:
                            return !$left;

                        case $node instanceof Node\Func\StringCollection\StartsWith:
                            return Str::startsWith(...$args);

                        case $node instanceof Node\Func\StringCollection\Substring:
                            return substr(...$args);

                        case $node instanceof Node\Func\StringCollection\EndsWith:
                            return Str::endsWith(...$args);

                        case $node instanceof Node\Func\StringCollection\Contains:
                            return Str::contains(...$args);

                        case $node instanceof Node\Func\StringCollection\Concat:
                            return join('', $args);

                        case $node instanceof Node\Func\StringCollection\Length:
                            return Str::length(...$args);

                        case $node instanceof Node\Func\StringCollection\IndexOf:
                            return strpos(...$args);

                        case $node instanceof Property:
                            return $item[$node->getValue()] ?? null;

                        case $node instanceof Literal:
                            return $node->getValue();

                        case $node instanceof Node\Func\Arithmetic\Round:
                            return round(...$args);

                        case $node instanceof Node\Func\Arithmetic\Ceiling:
                            return ceil(...$args);

                        case $node instanceof Node\Func\Arithmetic\Floor:
                            return floor(...$args);

                        case $node instanceof Node\Func\String\ToLower:
                            return strtolower(...$args);

                        case $node instanceof Node\Func\String\ToUpper:
                            return strtoupper(...$args);

                        case $node instanceof Node\Func\String\Trim:
                            return trim(...$args);

                        case $node instanceof Node\Func\String\MatchesPattern:
                            return 1 === preg_match('/'.$args[1].'/', $args[0]);

                        case $node instanceof Operator\Arithmetic\Add:
                            return $left + $right;

                        case $node instanceof Operator\Arithmetic\Sub:
                            return $left - $right;

                        case $node instanceof Operator\Arithmetic\Div:
                            return $left / $right;

                        case $node instanceof Operator\Arithmetic\DivBy:
                            return (float) $left / (float) $right;

                        case $node instanceof Operator\Arithmetic\Mul:
                            return $left * $right;

                        case $node instanceof Operator\Arithmetic\Mod:
                            return $left % $right;

                        case $node instanceof Node\Func\DateTime\Day:
                            return $carbon->day;

                        case $node instanceof Node\Func\DateTime\Date:
                            return $carbon->format(Type\Date::DATE_FORMAT);

                        case $node instanceof Node\Func\DateTime\Hour:
                            return $carbon->hour;

                        case $node instanceof Node\Func\DateTime\Minute:
                            return $carbon->minute;

                        case $node instanceof Node\Func\DateTime\Month:
                            return $carbon->month;

                        case $node instanceof Node\Func\DateTime\Second:
                            return $carbon->second;

                        case $node instanceof Node\Func\DateTime\Time:
                            return $carbon->format(Type\TimeOfDay::DATE_FORMAT);

                        case $node instanceof Node\Func\DateTime\Year:
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

                        case $node instanceof Operator\Comparison\And_:
                            return $left && $right;

                        case $node instanceof Operator\Comparison\Or_:
                            return $left || $right;

                        case $node instanceof Operator\Comparison\Not_:
                            return !$left;
                    }

                    throw new NotImplementedException();
                };

                return !!$eval($tree);
            });
        }

        if ($this->getOrderBy()->hasValue()) {
            $collection = $collection->map(function ($item, $key) {
                return array_merge(['__id' => $key], $item);
            })
                ->sortBy($this->getOrderBy()->getSortOrders())
                ->keyBy('__id');
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