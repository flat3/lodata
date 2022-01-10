<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node\Operator\Logical;

use Flat3\Lodata\Expression\Node\Operator\Logical;

/**
 * Less Than Or Equal
 * @package Flat3\Lodata\Expression\Node\Operator\Logical
 */
class LessThanOrEqual extends Logical
{
    protected $symbol = 'le';
    protected $precedence = 4;
}
