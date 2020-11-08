<?php

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

/**
 * Double
 * @package Flat3\Lodata\Expression\Node\Literal
 */
class Double extends Literal
{
    public function getValue(): float
    {
        return (float) $this->value;
    }
}
