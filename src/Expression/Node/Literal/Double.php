<?php

namespace Flat3\OData\Expression\Node\Literal;

use Flat3\OData\Expression\Node\Literal;

class Double extends Literal
{
    public function setValue(string $value): void
    {
        $this->value = (float) $value;
    }

    public function getValue(): float
    {
        return $this->value;
    }
}
