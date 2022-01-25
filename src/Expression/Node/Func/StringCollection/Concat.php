<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\StringCollection;

use Flat3\Lodata\Expression\Node\Func;

/**
 * Concat
 * @package Flat3\Lodata\Expression\Node\Func\StringCollection
 */
class Concat extends Func
{
    public const symbol = 'concat';
    public const arguments = 2;
}
