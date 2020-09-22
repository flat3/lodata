<?php

namespace Flat3\OData\Expression\Node\Operator\Logical;

use Flat3\OData\Expression\Node\Operator\Logical;

class GreaterThan extends Logical
{
    public const symbol = 'gt';
    public const precedence = 4;
}
