<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\DateTime;

use Flat3\Lodata\Expression\Node\Func;

/**
 * MinDateTime
 * @package Flat3\Lodata\Expression\Node\Func\DateTime
 */
class MinDateTime extends Func
{
    protected $symbol = 'mindatetime';
    protected $argumentCount = 0;
}
