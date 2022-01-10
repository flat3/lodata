<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\StringCollection;

use Flat3\Lodata\Expression\Node\Func;

/**
 * Starts With
 * @package Flat3\Lodata\Expression\Node\Func\StringCollection
 */
class StartsWith extends Func
{
    protected $symbol = 'startswith';
    protected $argumentCount = 2;
}
