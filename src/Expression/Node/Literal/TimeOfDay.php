<?php

namespace Flat3\Lodata\Expression\Node\Literal;

use Carbon\Carbon;
use Flat3\Lodata\Expression\Node\Literal;

/**
 * Time Of Day
 * @package Flat3\Lodata\Expression\Node\Literal
 */
class TimeOfDay extends Literal
{
    public function getValue(): Carbon
    {
        return \Flat3\Lodata\Type\TimeOfDay::factory($this->value)->get();
    }
}
