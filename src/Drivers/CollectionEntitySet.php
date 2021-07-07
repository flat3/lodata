<?php

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Expression\Event;
use Flat3\Lodata\Expression\Node;
use Flat3\Lodata\Expression\Node\Func\Arithmetic\Ceiling;
use Flat3\Lodata\Expression\Node\Func\Arithmetic\Floor;
use Flat3\Lodata\Expression\Node\Func\Arithmetic\Round;
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
use Flat3\Lodata\Expression\Node\Operator\Comparison\And_;
use Flat3\Lodata\Expression\Node\Operator\Comparison\Not_;
use Flat3\Lodata\Expression\Node\Operator\Comparison\Or_;
use Flat3\Lodata\Expression\Node\Operator\Logical\Equal;
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

                    switch (true) {
                        case $node instanceof Equal:
                            return $left === null || $right === null ? $left === $right : $left == $right;

                        case $node instanceof NotEqual:
                            return $left === null || $right === null ? $left !== $right : $left != $right;

                        case $node instanceof Node\Operator\Logical\GreaterThan:
                            return $left !== null && $right !== null && $left > $right;

                        case $node instanceof Node\Operator\Logical\GreaterThanOrEqual:
                            return $left !== null && $right !== null && $left >= $right;

                        case $node instanceof Node\Operator\Logical\LessThan:
                            return $left !== null && $right !== null && $left < $right;

                        case $node instanceof Node\Operator\Logical\LessThanOrEqual:
                            return $left !== null && $right !== null && $left <= $right;

                        case $node instanceof Node\Operator\Logical\In:
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

                        case $node instanceof Node\Operator\Arithmetic\Add:
                            return $left + $right;

                        case $node instanceof Node\Operator\Arithmetic\Sub:
                            return $left - $right;

                        case $node instanceof Node\Operator\Arithmetic\Div:
                            return $left / $right;

                        case $node instanceof Node\Operator\Arithmetic\DivBy:
                            return (float) $left / (float) $right;

                        case $node instanceof Node\Operator\Arithmetic\Mul:
                            return $left * $right;

                        case $node instanceof Node\Operator\Arithmetic\Mod:
                            return ($left !== null && $right !== null) ? $left % $right : null;
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