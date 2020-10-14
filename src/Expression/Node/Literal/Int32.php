<?php

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

class Int32 extends Literal
{
    public function getValue(): int
    {
        return (int)$this->value;
    }
}
