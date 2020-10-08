<?php

namespace Flat3\OData\Type;

use Flat3\OData\PrimitiveType;

class Enum extends PrimitiveType
{
    protected $name = 'Edm.Enum';

    /** @var ?int $value */
    protected $value;

    protected $values = [];

    public function set($value): void
    {
        $this->value = array_search($value, $this->values);
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
