<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Operator\Arithmetic;

use Flat3\Lodata\Expression\Node\Operator\Arithmetic;

/**
 * Mul
 * @package Flat3\Lodata\Expression\Node\Operator\Arithmetic
 */
class Mul extends Arithmetic
{
    public const symbol = 'mul';
    public const precedence = 6;
}
