<?php

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

class Int32 extends Literal
{
    public function setValue(string $value): void
    {
        $this->value = (int) $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }
}
