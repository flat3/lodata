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
    public const symbol = 'div';
    public const precedence = 6;
}
