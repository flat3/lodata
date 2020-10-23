<?php

namespace Flat3\Lodata\Type;

use ArrayAccess;
use Flat3\Lodata\Primitive;

class Collection extends Primitive implements ArrayAccess
{
    protected $value = [];

    public function set($value): self
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

    public function offsetExists($offset)
    {
        return isset($this->value[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->value[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->value[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->value[$offset]);
    }
}
