<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers;

use ArrayAccess;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Interfaces\EntitySet\CountInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Generator;

/**
 * Class StaticEntitySet
 * The static entity set is assigned entities and does not query a data source
 * @package Flat3\Lodata\Drivers
 */
class StaticEntitySet extends EntitySet implements QueryInterface, ArrayAccess, CountInterface
{
    /**
     * @var Entity[] $results
     */
    protected $results = [];

    public function __construct(EntityType $entityType)
    {
        parent::__construct('unbound', $entityType);

        $this->applyQueryOptions = false;
    }

    /**
     * Return all results
     */
    public function query(): Generator
    {
        foreach ($this->results as $result) {
            yield $result;
        }
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->results);
    }

    public function offsetGet($offset)
    {
        return $this->results[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if ($offset) {
            $this->results[$offset] = $value;
        } else {
            $this->results[] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset ($this->results[$offset]);
    }

    public function rewind()
    {
        reset($this->results);
    }

    /**
     * Filter the objects in the array
     * @param  callable  $callback
     * @return $this
     */
    public function filter(callable $callback): self
    {
        $this->results = array_filter($this->results, $callback);

        return $this;
    }

    /**
     * Sort the objects in the array
     * @param  callable  $callback
     * @return $this
     */
    public function sort(callable $callback): self
    {
        uasort($this->results, $callback);

        return $this;
    }

    /**
     * Count the objects in the array
     * @return int
     */
    public function count(): int
    {
        return count($this->results);
    }
}