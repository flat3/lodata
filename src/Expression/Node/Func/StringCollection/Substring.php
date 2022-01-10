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
    protected $symbol = 'substring';
    protected $argumentCount = [2, 3];
}
