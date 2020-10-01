<?php

namespace Flat3\OData\Expression\Node\Literal;

use Flat3\OData\Expression\Node\Literal;

class Date extends Literal
{
    public function setValue(string $value): void
    {
        $this->value = \Flat3\OData\Type\Date::factory($value)
            ->get()
            ->format('Y-m-d');
    }
}
