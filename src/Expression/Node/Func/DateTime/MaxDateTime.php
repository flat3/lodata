<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\DateTime;

use Flat3\Lodata\Expression\Node\Func;

/**
 * MaxDateTime
 * @package Flat3\Lodata\Expression\Node\Func\DateTime
 */
class MaxDateTime extends Func
{
    protected $symbol = 'maxdatetime';
    protected $argumentCount = 0;
}
