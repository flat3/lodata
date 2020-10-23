<?php

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Primitive;

class Enum extends Primitive
{
    const identifier = 'Edm.Enum';

    /** @var ?int $value */
    protected $value;

    protected $values = [];

    public function set($value): self
    {
        $this->value = array_search($value, $this->values);

        return $this;
    }

    public function add($value, ?int $position = null): self
    {
        if ($position) {
            $this->values[$position] = $value;
        } else {
            $this->values[] = $value;
        }

        return $this;
    }

    public function toUrl(): string
    {
        return $this->values[$this->value];
    }

    public function toJson()
    {
        return $this->values[$this->value];
    }
}
