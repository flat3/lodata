<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use ArrayAccess;
use Flat3\Lodata\Primitive;

/**
 * Collection
 * @package Flat3\Lodata\Type
 */
class Collection extends Primitive implements ArrayAccess
{
    protected $value = [];

    public function __construct($value = null, bool $nullable = true)
    {
        parent::__construct($value, $nullable);
        $this->value = $value == null ? [] : [$value];
    }

    public function add($value): self
    {
        $this->value[] = $value;

        return $this;
    }

    public function toUrl(): string
    {
        return implode(',', array_map(function (Primitive $value) {
            return $value->get();
        }, $this->value));
    }

    public function toJson()
    {
        return array_map(function (Primitive $value) {
            return $value->toJson();
        }, $this->value);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->value[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->value[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->value[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->value[$offset]);
    }

    public function set($value)
    {
        $this->value = [$value];

        return $this;
    }
}
