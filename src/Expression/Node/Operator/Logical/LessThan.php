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
    protected $symbol = 'lt';
    protected $precedence = 4;
}
