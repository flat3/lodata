<?php

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;
use Flat3\Lodata\Helper\Constants;

class Boolean extends Literal
{
    public function getValue(): bool
    {
        return Constants::TRUE === $this->value;
    }
}
