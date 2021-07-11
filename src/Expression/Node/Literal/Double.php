<?php

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

/**
 * Double
 * @package Flat3\Lodata\Expression\Node\Literal
 */
class Double extends Literal
{
    public function getValue(): \Flat3\Lodata\Type\Double
    {
        return \Flat3\Lodata\Type\Double::factory($this->value);
    }
}
