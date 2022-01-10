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
    protected $symbol = 'not';
    protected $unary = true;
    protected $precedence = 7;
}
