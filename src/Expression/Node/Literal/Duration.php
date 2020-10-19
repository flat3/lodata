<?php

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

class Duration extends Literal
{
    public function getValue(): string
    {
        return \Flat3\Lodata\Type\Duration::factory($this->value)->get();
    }
}
