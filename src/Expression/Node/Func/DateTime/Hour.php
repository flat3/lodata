<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\DateTime;

use Flat3\Lodata\Expression\Node\Func;

/**
 * Hour
 * @package Flat3\Lodata\Expression\Node\Func\DateTime
 */
class Hour extends Func
{
    public const symbol = 'hour';
    public const arguments = 1;
}
