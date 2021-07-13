<?php

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Expression\Event;
use Flat3\Lodata\Expression\Node;
use Flat3\Lodata\Expression\Node\Literal;
use Flat3\Lodata\Expression\Node\Operator;
use Flat3\Lodata\Expression\Parser\Filter as FilterParser;
use Flat3\Lodata\Expression\Parser\Search as SearchParser;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\EntitySet\CountInterface;
use Flat3\Lodata\Interfaces\EntitySet\FilterInterface;
use Flat3\Lodata\Interfaces\EntitySet\OrderByInterface;
use Flat3\Lodata\Interfaces\EntitySet\PaginationInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\EntitySet\ReadInterface;
use Flat3\Lodata\Interfaces\EntitySet\SearchInterface;
use Generator;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;

abstract class EnumerableEntitySet extends EntitySet implements ReadInterface, QueryInterface, CountInterface, PaginationInterface, OrderByInterface, SearchInterface, FilterInterface
{
    /** @var Enumerable|Collection|LazyCollection $enumerable */
    protected $enumerable;

    public function query(): Generator
    {
        $collection = $this->enumerable;

        if ($this->getFilter()->hasValue()) {
            $parser = new FilterParser($this->getTransaction());
            $parser->pushEntitySet($this);

            $tree = $parser->generateTree($this->getFilter()->getValue());

            $collection = $collection->filter(function ($item) use ($tree) {
                $result = $tree->evaluateCommonExpression($this->newEntity()->fromArray($item));
                return $result !== null && !!$result->get();
            });
        }

        if ($this->getSearch()->hasValue()) {
            $search = $this->getSearch();

            $parser = new SearchParser();
            $parser->pushEntitySet($this);

            $tree = $parser->generateTree($search->getValue());

            $collection = $collection->filter(function ($item) use ($tree) {
                $result = $tree->evaluateSearchExpression($this->newEntity()->fromArray($item));
                return $result !== null && !!$result->get();
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

    public function count(): int
    {
        return $this->enumerable->count();
    }

    public function search(Event $event): ?bool
    {
        return true;
    }

    public function filter(Event $event): ?bool
    {
        return true;
    }

    public function read(PropertyValue $key): ?Entity
    {
        $item = $this->enumerable->get($key->getPrimitiveValue()->get());

        if ($item === null) {
            return null;
        }

        $entity = $this->newEntity();
        $entity['id'] = $key->getPrimitiveValue()->get();
        $entity->fromArray($item);

        return $entity;
    }
}