<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\DateTime;

use Flat3\Lodata\Expression\Node\Func;

/**
 * Minute
 * @package Flat3\Lodata\Expression\Node\Func\DateTime
 */
class Minute extends Func
{
    public const symbol = 'minute';
    public const arguments = 1;
}
