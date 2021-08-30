<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Expression\Node;
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

abstract class EnumerableEntitySet extends EntitySet implements ReadInterface, QueryInterface, CountInterface, PaginationInterface, OrderByInterface, SearchInterface, FilterInterface
{
    /** @var Enumerable|Collection|LazyCollection $enumerable */
    protected $enumerable;

    /**
     * Query this entity set
     * @return Generator
     */
    public function query(): Generator
    {
        $enumerable = $this->enumerable;

        if ($this->getFilter()->hasValue()) {
            $parser = new FilterParser($this->getTransaction());
            $parser->pushEntitySet($this);

            $tree = $parser->generateTree($this->getFilter()->getValue());

            $enumerable = $enumerable->filter(function ($item) use ($tree) {
                $result = $tree->evaluateCommonExpression($this->newEntity()->fromSource($item));
                return $result !== null && !!$result->get();
            });
        }

        if ($this->getSearch()->hasValue()) {
            $search = $this->getSearch();

            $parser = new SearchParser();
            $parser->pushEntitySet($this);

            $tree = $parser->generateTree($search->getValue());

            $enumerable = $enumerable->filter(function ($item) use ($tree) {
                $result = $tree->evaluateSearchExpression($this->newEntity()->fromSource($item));
                return $result !== null && !!$result->get();
            });
        }

        if ($this->getOrderBy()->hasValue()) {
            $enumerable = $enumerable->map(function ($item, $key) {
                return array_merge(['__id' => $key], $item);
            })
                ->sortBy($this->getOrderBy()->getSortOrders())
                ->mapWithKeys(function ($item) {
                    $key = $item['__id'];
                    unset($item['__id']);
                    return [$key => $item];
                });
        }

        if ($this->getSkip()->hasValue()) {
            $enumerable = $enumerable->skip($this->getSkip()->getValue());
        }

        if ($this->getTop()->hasValue()) {
            $enumerable = $enumerable->slice(0, $this->getTop()->getValue());
        }

        foreach ($enumerable->all() as $key => $item) {
            $entity = $this->newEntity();
            $entity->setEntityId($key);
            $entity->fromSource($item);

            yield $entity;
        }
    }

    /**
     * Count entities in this set
     * @return int
     */
    public function count(): int
    {
        return $this->enumerable->count();
    }

    public function search(Node $node): ?bool
    {
        return true;
    }

    public function filter(Node $node): ?bool
    {
        return true;
    }

    /**
     * Read an entity from the set
     * @param  PropertyValue  $key
     * @return Entity|null
     */
    public function read(PropertyValue $key): ?Entity
    {
        $item = $this->enumerable->get($key->getPrimitiveValue());

        if ($item === null) {
            return null;
        }

        $entity = $this->newEntity();
        $entity['id'] = $key->getPrimitiveValue();
        $entity->fromSource($item);

        return $entity;
    }
}