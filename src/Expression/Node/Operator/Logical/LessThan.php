<?php

namespace Flat3\OData\Expression\Node\Operator\Logical;

use Flat3\OData\Expression\Node\Operator\Logical;

class LessThan extends Logical
{
    public const symbol = 'lt';
    public const precedence = 4;
}
