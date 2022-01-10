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
    protected $symbol = 'in';
    protected $precedence = 8;
    protected $unary = true;
}
