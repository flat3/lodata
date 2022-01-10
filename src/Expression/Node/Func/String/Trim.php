<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\String;

use Flat3\Lodata\Expression\Node\Func;

/**
 * Trim
 * @package Flat3\Lodata\Expression\Node\Func\String
 */
class Trim extends Func
{
    protected $symbol = 'trim';
    protected $argumentCount = 1;
}
