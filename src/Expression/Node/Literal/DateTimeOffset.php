<?php

namespace Flat3\Lodata\Expression\Node\Literal;

use Carbon\Carbon;
use Flat3\Lodata\Expression\Node\Literal;

/**
 * DateTimeOffset
 * @package Flat3\Lodata\Expression\Node\Literal
 */
class DateTimeOffset extends Literal
{
    public function getValue(): Carbon
    {
        return \Flat3\Lodata\Type\DateTimeOffset::factory($this->value)->get();
    }
}
