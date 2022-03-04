<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Expression\Parser\Common;
use Flat3\Lodata\Expression\Parser\Search;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\EntitySet\ComputeInterface;
use Flat3\Lodata\Interfaces\EntitySet\CountInterface;
use Flat3\Lodata\Interfaces\EntitySet\FilterInterface;
use Flat3\Lodata\Interfaces\EntitySet\OrderByInterface;
use Flat3\Lodata\Interfaces\EntitySet\PaginationInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\EntitySet\ReadInterface;
use Flat3\Lodata\Interfaces\EntitySet\SearchInterface;
use Flat3\Lodata\Primitive;
use Generator;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\LazyCollection;

abstract class EnumerableEntitySet extends EntitySet implements ReadInterface, QueryInterface, CountInterface, PaginationInterface, OrderByInterface, SearchInterface, FilterInterface, ComputeInterface
{
    /** @var Enumerable|Collection|LazyCollection $enumerable */
    protected $enumerable;

    public function __construct(string $identifier, ?EntityType $entityType = null)
    {
        parent::__construct($identifier, $entityType);

        $this->enumerable = new Collection();
    }

    /**
     * Query this entity set
     * @return Generator
     */
    public function query(): Generator
    {
        $enumerable = $this->enumerable->map([$this, 'fillRecord']);

        if ($this->getCompute()->hasValue()) {
            $computedProperties = $this->getCompute()->getProperties();

            foreach ($computedProperties as $computedProperty) {
                $computeParser = $this->getComputeParser();
                $computeParser->pushEntitySet($this);

                $tree = $computeParser->generateTree($computedProperty->getExpression());

                $enumerable = $enumerable->map(function ($item) use ($computedProperty, $tree) {
                    $value = Common::evaluate($tree, $this->toEntity($item));
                    $item[$computedProperty->getName()] = $value instanceof Primitive ? $value->get() : $value;

                    return $item;
                });
            }
        }

        $enumerable = $this->applyFilter($enumerable);

        if ($this->getSearch()->hasValue()) {
            $search = $this->getSearch();

            $this->assertValidSearch();

            $parser = $this->getSearchParser();
            $parser->pushEntitySet($this);

            $tree = $parser->generateTree($search->getValue());

            $enumerable = $enumerable->filter(function ($item) use ($tree) {
                $result = Search::evaluate($tree, $this->toEntity($item));
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
            yield $this->toEntity($item, $key);
        }
    }

    /**
     * Apply the filter option to the enumerable
     * @param  Enumerable  $enumerable
     * @return Enumerable
     */
    protected function applyFilter(Enumerable $enumerable): Enumerable
    {
        if (!$this->getFilter()->hasValue()) {
            return $enumerable;
        }

        $parser = $this->getFilterParser();
        $parser->pushEntitySet($this);

        $tree = $parser->generateTree($this->getFilter()->getExpression());

        return $enumerable->filter(function ($item) use ($tree) {
            $result = Common::evaluate($tree, $this->toEntity($item));
            return $result !== null && !!$result->get();
        });
    }

    public function fillRecord(array $item): array
    {
        foreach ($this->getType()->getDeclaredProperties() as $property) {
            if ($this->getType()->getKey() === $property) {
                continue;
            }

            if (!array_key_exists($property->getName(), $item)) {
                $item[$property->getName()] = null;
            }
        }

        return $item;
    }

    /**
     * Count entities in this set
     * @return int
     */
    public function count(): int
    {
        return $this->applyFilter($this->enumerable->map([$this, 'fillRecord']))->count();
    }

    /**
     * Read an entity from the set
     * @param  PropertyValue  $key
     * @return Entity|null
     */
    public function read(PropertyValue $key): Entity
    {
        $item = $this->enumerable->get($key->getPrimitiveValue());

        if ($item === null) {
            throw new NotFoundException('entity_not_found', 'Entity not found');
        }

        $item = $this->fillRecord($item);

        return $this->toEntity($item, $key)->generateComputedProperties();
    }
}