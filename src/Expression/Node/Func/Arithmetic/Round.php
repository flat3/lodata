<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\Arithmetic;

use Flat3\Lodata\Expression\Node\Func;

/**
 * Round
 * @package Flat3\Lodata\Expression\Node\Func\Arithmetic
 */
class Round extends Func
{
    protected $symbol = 'round';
    protected $argumentCount = 1;
}
