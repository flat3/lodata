<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\DateTime;

use Flat3\Lodata\Expression\Node\Func;

/**
 * FractionalSeconds
 * @package Flat3\Lodata\Expression\Node\Func\DateTime
 */
class FractionalSeconds extends Func
{
    public const symbol = 'fractionalseconds';
    public const arguments = 1;
}
