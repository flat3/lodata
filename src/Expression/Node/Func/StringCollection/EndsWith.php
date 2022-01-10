<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\StringCollection;

use Flat3\Lodata\Expression\Node\Func;

/**
 * Ends With
 * @package Flat3\Lodata\Expression\Node\Func\StringCollection
 */
class EndsWith extends Func
{
    protected $symbol = 'endswith';
    protected $argumentCount = 2;
}
