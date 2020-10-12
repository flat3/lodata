<?php

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

class TimeOfDay extends Literal
{
    public function setValue(string $value): void
    {
        $this->value = \Flat3\Lodata\Type\TimeOfDay::factory($value)
            ->get()
            ->format('H:i:s');
    }
}
