<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\DateTime;

use Flat3\Lodata\Expression\Node\Func;

/**
 * Date
 * @package Flat3\Lodata\Expression\Node\Func\DateTime
 */
class Date extends Func
{
    protected $symbol = 'date';
    protected $argumentCount = 1;
}
