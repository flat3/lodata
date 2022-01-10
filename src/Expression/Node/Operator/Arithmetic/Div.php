<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Operator\Arithmetic;

use Flat3\Lodata\Expression\Node\Operator\Arithmetic;

/**
 * Div
 * @package Flat3\Lodata\Expression\Node\Operator\Arithmetic
 */
class Div extends Arithmetic
{
    protected $symbol = 'div';
    protected $precedence = 6;
}
