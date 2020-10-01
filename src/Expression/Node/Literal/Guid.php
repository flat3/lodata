<?php

namespace Flat3\OData\Expression\Node\Literal;

use Flat3\OData\Expression\Node\Literal;

class Guid extends Literal
{
    public function setValue(string $value): void
    {
        $this->value = \Flat3\OData\Type\Guid::factory($value)->get();
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
