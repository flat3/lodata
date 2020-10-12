<?php

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

class DateTimeOffset extends Literal
{
    public function setValue(string $value): void
    {
        $this->value = \Flat3\Lodata\Type\DateTimeOffset::factory($value)
            ->get()
            ->format('c');
    }
}
