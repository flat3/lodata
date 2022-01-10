<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\DateTime;

use Flat3\Lodata\Expression\Node\Func;

/**
 * Second
 * @package Flat3\Lodata\Expression\Node\Func\DateTime
 */
class Second extends Func
{
    protected $symbol = 'second';
    protected $argumentCount = 1;
}
