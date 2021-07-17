<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\DateTime;

use Flat3\Lodata\Expression\Node\Func;

/**
 * TotalSeconds
 * @package Flat3\Lodata\Expression\Node\Func\DateTime
 */
class TotalSeconds extends Func
{
    public const symbol = 'totalseconds';
    public const arguments = 1;
}
