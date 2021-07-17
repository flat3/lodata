<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Literal;

use Flat3\Lodata\Expression\Node\Literal;

/**
 * Null
 * @package Flat3\Lodata\Expression\Node\Literal
 */
class Null_ extends Literal
{
    public function getValue()
    {
        return null;
    }
}
