<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Operator\Logical;

use Flat3\Lodata\Expression\Node\Operator\Logical;

/**
 * Equal
 * @package Flat3\Lodata\Expression\Node\Operator\Logical
 */
class Equal extends Logical
{
    public const symbol = 'eq';
    public const precedence = 3;
}
