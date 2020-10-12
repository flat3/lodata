<?php

namespace Flat3\Lodata\Expression\Node\Operator\Logical;

use Flat3\Lodata\Expression\Node\Operator\Logical;

class LessThanOrEqual extends Logical
{
    public const symbol = 'le';
    public const precedence = 4;
}
