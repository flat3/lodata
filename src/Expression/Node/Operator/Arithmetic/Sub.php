<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Operator\Arithmetic;

use Flat3\Lodata\Expression\Node\Operator\Arithmetic;

/**
 * Sub
 * @package Flat3\Lodata\Expression\Node\Operator\Arithmetic
 */
class Sub extends Arithmetic
{
    protected $symbol = 'sub';
    protected $precedence = 5;
}
