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
    protected $symbol = 'totalseconds';
    protected $argumentCount = 1;
}
