<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Operator\Logical;

use Flat3\Lodata\Expression\Node\Operator\Logical;

/**
 * Less Than
 * @package Flat3\Lodata\Expression\Node\Operator\Logical
 */
class LessThan extends Logical
{
    public const symbol = 'lt';
    public const precedence = 4;
}
