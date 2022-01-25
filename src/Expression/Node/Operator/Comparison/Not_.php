<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Operator\Comparison;

use Flat3\Lodata\Expression\Node\Operator\Comparison;

/**
 * Not
 * @package Flat3\Lodata\Expression\Node\Operator\Comparison
 */
class Not_ extends Comparison
{
    public const symbol = 'not';
    public const unary = true;
    public const precedence = 7;
}
