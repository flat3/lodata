<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\String;

use Flat3\Lodata\Expression\Node\Func;

/**
 * To Upper
 * @package Flat3\Lodata\Expression\Node\Func\String
 */
class ToUpper extends Func
{
    public const symbol = 'toupper';
    public const arguments = 1;
}
