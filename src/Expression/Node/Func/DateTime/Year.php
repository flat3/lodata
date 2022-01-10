<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\DateTime;

use Flat3\Lodata\Expression\Node\Func;

/**
 * Year
 * @package Flat3\Lodata\Expression\Node\Func\DateTime
 */
class Year extends Func
{
    protected $symbol = 'year';
    protected $argumentCount = 1;
}
