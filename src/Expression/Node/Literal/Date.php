<?php

namespace Flat3\Lodata\Expression\Node\Literal;

use Carbon\Carbon;
use Flat3\Lodata\Expression\Node\Literal;

/**
 * Date
 * @package Flat3\Lodata\Expression\Node\Literal
 */
class Date extends Literal
{
    public function getValue(): Carbon
    {
        return \Flat3\Lodata\Type\Date::factory($this->value)->get();
    }
}
