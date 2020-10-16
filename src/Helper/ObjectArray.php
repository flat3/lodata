<?php

namespace Flat3\Lodata\Helper;

use ArrayAccess;
use Countable;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Iterator;

class ObjectArray implements Countable, Iterator, ArrayAccess
{
    private $array = [];

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

    public function replace($key, $value = null): void
    {
        if (!$key) {
            $key = $value;
        }

        if (!$value) {
            $value = $key;
        }

        $this->array[(string) $key] = $value;
    }

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

    public function count()
    {
        return count($this->array);
    }

    public function exists($key): bool
    {
        return array_key_exists((string) $key, $this->array);
    }

    public function current()
    {
        return current($this->array);
    }

    public function next()
    {
        return next($this->array);
    }

    public function key()
    {
        return key($this->array);
    }

    public function valid()
    {
        $key = key($this->array);

        return !!$key;
    }

    public function rewind()
    {
        reset($this->array);
    }

    public function offsetExists($offset)
    {
        return !!$this->get($offset);
    }

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

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->replace($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->drop($offset);
    }

    public function drop($key): void
    {
        unset($this->array[(string) $key]);
    }

    public function hasEntries(): bool
    {
        return !!$this->array;
    }

    public function sliceByClass($class): self
    {
        $result = new self();
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

    public function filter(callable $callback): self
    {
        $result = new self();

        foreach ($this->array as $key => $value) {
            if ($callback($value, $key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public function sort(callable $callback): self
    {
        $result = new self();

        foreach ($this->array as $key => $value) {
            $result->array[$key] = $value;
        }

        uasort($result->array, $callback);

        return $result;
    }

    public function hash(): string
    {
        ksort($this->array);
        return hash('sha256', serialize($this->array));
    }

    public function keys(): array
    {
        return array_keys($this->array);
    }

    public function pick(array $keys): ObjectArray
    {
        return $this->filter(function ($obj, $key) use ($keys) {
            return in_array($key, $keys);
        });
    }
}
