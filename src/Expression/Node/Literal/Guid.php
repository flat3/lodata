<?php

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

class Guid extends Literal
{
    public function setValue(string $value): void
    {
        $this->value = \Flat3\Lodata\Type\Guid::factory($value)->get();
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
