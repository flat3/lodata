<?php

namespace Flat3\Lodata\Expression\Node\Operator\Arithmetic;

use Flat3\Lodata\Expression\Node\Operator\Arithmetic;

/**
 * Add
 * @package Flat3\Lodata\Expression\Node\Operator\Arithmetic
 */
class Add extends Arithmetic
{
    public const symbol = 'add';
    public const precedence = 5;
}
