<?php

namespace Flat3\Lodata\Expression\Node\Operator\Logical;

use Flat3\Lodata\Expression\Node\Operator\Logical;

class GreaterThanOrEqual extends Logical
{
    public const symbol = 'ge';
    public const precedence = 4;
}
