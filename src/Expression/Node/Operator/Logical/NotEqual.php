<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Operator\Logical;

use Flat3\Lodata\Expression\Node\Operator\Logical;

/**
 * Not Equal
 * @package Flat3\Lodata\Expression\Node\Operator\Logical
 */
class NotEqual extends Logical
{
    protected $symbol = 'ne';
    protected $precedence = 3;
}
