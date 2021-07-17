<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\DateTime;

use Flat3\Lodata\Expression\Node\Func;

/**
 * Month
 * @package Flat3\Lodata\Expression\Node\Func\DateTime
 */
class Month extends Func
{
    public const symbol = 'month';
    public const arguments = 1;
}
