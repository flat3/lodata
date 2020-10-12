<?php

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

class Date extends Literal
{
    public function setValue(string $value): void
    {
        $this->value = \Flat3\Lodata\Type\Date::factory($value)
            ->get()
            ->format('Y-m-d');
    }
}
