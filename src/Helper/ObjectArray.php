<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper;

use ArrayAccess;
use Countable;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Illuminate\Support\Arr;
use Iterator;
use TypeError;

/**
 * Object Array
 * @package Flat3\Lodata\Helper
 */
class ObjectArray implements Countable, Iterator, ArrayAccess
{
    /**
     * Internal content
     * @var array
     */
    private $array = [];

    /**
     * List of valid types for this object array
     * @var string[] Types
     */
    protected $types = [];

    /**
     * Merge two object arrays
     * @param  ObjectArray  $map_a
     * @param  ObjectArray  $map_b
     * @return ObjectArray
     */
    public static function merge(ObjectArray $map_a, ObjectArray $map_b): ObjectArray
    {
        $map = new self();

        foreach ($map_a as $a) {
            $map->replace($a);
        }

        foreach ($map_b as $b) {
            $map->replace($b);
        }

        return $map;
    }

    /**
     * Replace a value in the array
     * @param  mixed  $key
     * @param  null  $value
     */
    public function replace($key, $value = null): void
    {
        if (!$key) {
            $key = $value;
        }

        if (!$value) {
            $value = $key;
        }

        if ($this->types) {
            foreach ($this->types as $type) {
                if ($value instanceof $type) {
                    break;
                }

                throw new TypeError('The provided class type was not valid for this object array');
            }
        }

        $this->array[(string) $key] = $value;
    }

    /**
     * Add a value to the array
     * @param  mixed  $key
     * @param  null  $value
     */
    public function add($key, $value = null): void
    {
        if ($this->exists($key)) {
            throw new InternalServerErrorException(
                'cannot_add_existing_key',
                'Attempted to add an item that already exists'
            );
        }

        $this->replace($key, $value);
    }

    /**
     * Count values in the array
     * @return int
     */
    public function count()
    {
        return count($this->array);
    }

    /**
     * Check if the provided item exists in the array
     * @param  mixed  $key
     * @return bool
     */
    public function exists($key): bool
    {
        return array_key_exists((string) $key, $this->array);
    }

    /**
     * Get the current value in the array
     * @return mixed
     */
    public function current()
    {
        return current($this->array);
    }

    /**
     * Move to the next value in the array
     * @return mixed|void
     */
    public function next()
    {
        return next($this->array);
    }

    /**
     * Get the key of the current value in the array
     * @return string
     */
    public function key()
    {
        return key($this->array);
    }

    /**
     * Check if the current value in the array is valid
     * @return bool
     */
    public function valid()
    {
        $key = key($this->array);

        return !!$key;
    }

    /**
     * Rewind the array
     */
    public function rewind()
    {
        reset($this->array);
    }

    /**
     * Check if the provided offset exists in the array
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return !!$this->get($offset);
    }

    /**
     * Get a value from the array
     * @param  mixed  $key  Key
     * @return mixed|null
     */
    public function get($key)
    {
        $key = (string) $key;
        $result = $this->array[$key] ?? null;

        if ($result) {
            return $result;
        }

        foreach ($this->array as $k => $v) {
            if (!$v instanceof IdentifierInterface) {
                continue;
            }

            if ($v->getName() === $key) {
                return $v;
            }
        }

        return null;
    }

    /**
     * Get an object in the array
     * @param  mixed  $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Set an object in the array
     * @param  mixed  $offset
     * @param  mixed  $value
     */
    public function offsetSet($offset, $value)
    {
        $this->replace($offset, $value);
    }

    /**
     * Unset an object in the array
     * @param  mixed  $offset
     */
    public function offsetUnset($offset)
    {
        $this->drop($offset);
    }

    /**
     * Remove an object from the array
     * @param  mixed  $key
     */
    public function drop($key): void
    {
        unset($this->array[(string) $key]);
    }

    /**
     * Check whether the array is empty
     * @return bool
     */
    public function hasEntries(): bool
    {
        return !!$this->array;
    }

    /**
     * Get a subset of objects in the array by the provided class
     * @param  string|array  $class
     * @return $this
     */
    public function sliceByClass($class): self
    {
        $result = new $this();
        $classes = is_array($class) ? $class : [$class];

        foreach ($this->array as $key => $value) {
            foreach ($classes as $class) {
                if ($value instanceof $class) {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Filter the objects in the array
     * @param  callable  $callback
     * @return $this
     */
    public function filter(callable $callback): self
    {
        $result = new $this();

        foreach ($this->array as $key => $value) {
            if ($callback($value, $key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Map over the objects in the array
     * @param  callable  $callback
     * @return mixed
     */
    public function map(callable $callback)
    {
        return array_map($callback, $this->array);
    }

    /**
     * Sort the objects in the array
     * @param  callable  $callback
     * @return $this
     */
    public function sort(callable $callback): self
    {
        $result = new $this();

        foreach ($this->array as $key => $value) {
            $result->array[$key] = $value;
        }

        uasort($result->array, $callback);

        return $result;
    }

    /**
     * Get a a list of object keys
     * @return array
     */
    public function keys(): array
    {
        return array_keys($this->array);
    }

    /**
     * Pick a subset of the array objects by the provided keys
     * @param  array  $keys
     * @return ObjectArray
     */
    public function pick(array $keys): ObjectArray
    {
        return $this->filter(function ($_, $key) use ($keys) {
            return in_array($key, $keys);
        });
    }

    /**
     * Clear the array
     * @return $this
     */
    public function clear(): self
    {
        $this->array = [];
        return $this;
    }

    /**
     * Get the first element of the array
     * @return mixed|null
     */
    public function first()
    {
        return Arr::first($this->array);
    }

    /**
     * Return the array
     * @return array
     */
    public function all(): array
    {
        return array_merge([], $this->array);
    }
}
