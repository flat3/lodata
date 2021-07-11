<?php

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

/**
 * Int32
 * @package Flat3\Lodata\Expression\Node\Literal
 */
class Int32 extends Literal
{
    public function getValue(): \Flat3\Lodata\Type\Int32
    {
        return \Flat3\Lodata\Type\Int32::factory($this->value);
    }
}
