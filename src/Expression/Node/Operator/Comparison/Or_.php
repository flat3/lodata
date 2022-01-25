<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Operator\Comparison;

use Flat3\Lodata\Expression\Node\Operator\Comparison;

/**
 * Or
 * @package Flat3\Lodata\Expression\Node\Operator\Comparison
 */
class Or_ extends Comparison
{
    public const symbol = 'or';
    public const precedence = 1;
}
