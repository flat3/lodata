<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\DateTime;

use Flat3\Lodata\Expression\Node\Func;

/**
 * Day
 * @package Flat3\Lodata\Expression\Node\Func\DateTime
 */
class Day extends Func
{
    public const symbol = 'day';
    public const arguments = 1;
}
