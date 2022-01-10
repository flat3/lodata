<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Func\StringCollection;

use Flat3\Lodata\Expression\Node\Func;

/**
 * Contains
 * @package Flat3\Lodata\Expression\Node\Func\StringCollection
 */
class Contains extends Func
{
    protected $symbol = 'contains';
    protected $argumentCount = 2;
}
