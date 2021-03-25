<?php

namespace Flat3\Lodata\Drivers;

use ArrayAccess;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;

/**
 * Class ManualEntitySet
 * The manual entity set is assigned entities and does not query a data source
 * @package Flat3\Lodata\Drivers
 */
class ManualEntitySet extends EntitySet implements QueryInterface, ArrayAccess
{
    public function __construct(EntityType $entityType)
    {
        parent::__construct('unbound', $entityType);

        $this->applyQueryOptions = false;
        $this->results = [];
    }

    /**
     * Return no more results
     * @return array
     */
    public function query(): array
    {
        return [];
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
}