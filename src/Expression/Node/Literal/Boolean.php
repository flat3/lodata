<?php

namespace Flat3\OData\Expression\Node\Literal;

use Flat3\OData\Expression\Node\Literal;
use Flat3\OData\Helper\Constants;

class Boolean extends Literal
{
    public function setValue(string $value): void
    {
        $this->value = Constants::TRUE === $value;
    }

    public function getValue(): bool
    {
        return $this->value;
    }
}
