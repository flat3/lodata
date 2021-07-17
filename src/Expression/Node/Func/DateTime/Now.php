<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\DateTime;

use Flat3\Lodata\Expression\Node\Func;

/**
 * Now
 * @package Flat3\Lodata\Expression\Node\Func\DateTime
 */
class Now extends Func
{
    public const symbol = 'now';
    public const arguments = 0;
}
