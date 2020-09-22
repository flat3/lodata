<?php

namespace Flat3\OData\Expression\Node\Literal;

use Flat3\OData\Expression\Node\Literal;

class DateTimeOffset extends Literal
{
    public function setValue(string $value): void
    {
        $this->value = strtotime($value);
    }
}
