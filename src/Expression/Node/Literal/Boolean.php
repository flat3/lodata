<?php

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;
use Flat3\Lodata\Helper\Constants;

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
