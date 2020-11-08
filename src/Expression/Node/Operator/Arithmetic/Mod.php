<?php

namespace Flat3\Lodata\Expression\Node\Operator\Arithmetic;

use Flat3\Lodata\Expression\Node\Operator\Arithmetic;

/**
 * Mod
 * @package Flat3\Lodata\Expression\Node\Operator\Arithmetic
 */
class Mod extends Arithmetic
{
    public const symbol = 'mod';
    public const precedence = 6;
}
