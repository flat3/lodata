<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Operator\Arithmetic;

use Flat3\Lodata\Expression\Node\Operator\Arithmetic;

/**
 * Mod
 * @package Flat3\Lodata\Expression\Node\Operator\Arithmetic
 */
class Mod extends Arithmetic
{
    protected $symbol = 'mod';
    protected $precedence = 6;
}
