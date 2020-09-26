<?php

namespace Flat3\OData\Expression\Node\Literal;

use Flat3\OData\Expression\Node\Literal;

class TimeOfDay extends Literal
{
    public function setValue(string $value): void
    {
        $this->value = \Flat3\OData\Type\TimeOfDay::type()
            ->factory($value)
            ->getInternalValue()
            ->format('H:i:s');
    }
}
