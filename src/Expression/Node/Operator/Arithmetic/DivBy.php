<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Operator\Arithmetic;

use Flat3\Lodata\Expression\Node\Operator\Arithmetic;

/**
 * Div By
 * @package Flat3\Lodata\Expression\Node\Operator\Arithmetic
 */
class DivBy extends Arithmetic
{
    public const symbol = 'divby';
    public const precedence = 6;
}
