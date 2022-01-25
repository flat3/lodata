<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Operator\Logical;

use Flat3\Lodata\Expression\Node\Operator\Logical;

/**
 * Greater Than
 * @package Flat3\Lodata\Expression\Node\Operator\Logical
 */
class GreaterThan extends Logical
{
    public const symbol = 'gt';
    public const precedence = 4;
}
