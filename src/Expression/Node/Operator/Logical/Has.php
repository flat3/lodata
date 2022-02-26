<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Operator\Logical;

use Flat3\Lodata\Expression\Node\Operator\Logical;

/**
 * Has
 * @package Flat3\Lodata\Expression\Node\Operator\Logical
 */
class Has extends Logical
{
    public const symbol = 'has';
    public const precedence = 8;
}
