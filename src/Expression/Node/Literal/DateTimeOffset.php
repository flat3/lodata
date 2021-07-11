<?php

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

/**
 * DateTimeOffset
 * @package Flat3\Lodata\Expression\Node\Literal
 */
class DateTimeOffset extends Literal
{
    public function getValue(): \Flat3\Lodata\Type\DateTimeOffset
    {
        return \Flat3\Lodata\Type\DateTimeOffset::factory($this->value);
    }
}
