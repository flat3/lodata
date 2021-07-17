<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\StringCollection;

use Flat3\Lodata\Expression\Node\Func;

/**
 * Length
 * @package Flat3\Lodata\Expression\Node\Func\StringCollection
 */
class Length extends Func
{
    public const symbol = 'length';
    public const arguments = 1;
}
