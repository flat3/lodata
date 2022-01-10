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
    protected $symbol = 'day';
    protected $argumentCount = 1;
}
