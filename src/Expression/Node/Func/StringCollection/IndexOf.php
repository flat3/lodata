<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\StringCollection;

use Flat3\Lodata\Expression\Node\Func;

/**
 * Index Of
 * @package Flat3\Lodata\Expression\Node\Func\StringCollection
 */
class IndexOf extends Func
{
    protected $symbol = 'indexof';
    protected $argumentCount = 2;
}
