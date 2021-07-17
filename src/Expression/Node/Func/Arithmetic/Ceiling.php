<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\Arithmetic;

use Flat3\Lodata\Expression\Node\Func;

/**
 * Ceiling
 * @package Flat3\Lodata\Expression\Node\Func\Arithmetic
 */
class Ceiling extends Func
{
    public const symbol = 'ceiling';
    public const arguments = 1;
}
