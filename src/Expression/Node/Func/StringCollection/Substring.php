<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\StringCollection;

use Flat3\Lodata\Expression\Node\Func;

/**
 * Substring
 * @package Flat3\Lodata\Expression\Node\Func\StringCollection
 */
class Substring extends Func
{
    public const symbol = 'substring';
    public const arguments = [2, 3];
}
