<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\DateTime;

use Flat3\Lodata\Expression\Node\Func;

/**
 * TotalOffsetMinutes
 * @package Flat3\Lodata\Expression\Node\Func\DateTime
 */
class TotalOffsetMinutes extends Func
{
    public const symbol = 'totaloffsetminutes';
    public const arguments = 1;
}
