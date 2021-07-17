<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\String;

use Flat3\Lodata\Expression\Node\Func;

/**
 * To Lower
 * @package Flat3\Lodata\Expression\Node\Func\String
 */
class ToLower extends Func
{
    public const symbol = 'tolower';
    public const arguments = 1;
}
