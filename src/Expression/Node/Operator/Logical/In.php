<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Operator\Logical;

use Flat3\Lodata\Expression\Node\Operator\Logical;

/**
 * In
 * @package Flat3\Lodata\Expression\Node\Operator\Logical
 */
class In extends Logical
{
    public const symbol = 'in';
    public const precedence = 8;
    public const unary = true;
}
