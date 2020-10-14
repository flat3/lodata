<?php

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

class Guid extends Literal
{
    public function getValue(): string
    {
        return \Flat3\Lodata\Type\Guid::factory($this->value)->get();
    }
}
