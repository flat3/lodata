<?php

namespace Flat3\OData\Expression\Node\Operator\Logical;

use Flat3\OData\Expression\Node\Operator\Logical;

class LessThanOrEqual extends Logical
{
    public const symbol = 'le';
    public const precedence = 4;
}
