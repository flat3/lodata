<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Operator\Logical;

use Flat3\Lodata\Expression\Node\Operator\Logical;

/**
 * Greater Than Or Equal
 * @package Flat3\Lodata\Expression\Node\Operator\Logical
 */
class GreaterThanOrEqual extends Logical
{
    public const symbol = 'ge';
    public const precedence = 4;
}
