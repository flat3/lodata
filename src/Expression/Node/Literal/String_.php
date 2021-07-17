<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

/**
 * String
 * @package Flat3\Lodata\Expression\Node\Literal
 */
class String_ extends Literal
{
    public function getValue(): \Flat3\Lodata\Type\String_
    {
        return \Flat3\Lodata\Type\String_::factory($this->value);
    }
}
