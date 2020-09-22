<?php

namespace Flat3\OData\Expression\Node\Literal;

use DateTimeInterface;
use Flat3\OData\Expression\Node\Literal;

class DateTimeOffset extends Literal
{
    public function setValue(string $value): void
    {
        $this->value = \Flat3\OData\Type\DateTimeOffset::type()->factory($value)->getInternalValue()->format(DateTimeInterface::ISO8601);
    }
}
