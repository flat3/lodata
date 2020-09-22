<?php

namespace Flat3\OData\Expression\Node\Operator\Logical;

use Flat3\OData\Expression\Node\Operator\Logical;

class NotEqual extends Logical
{
    public const symbol = 'ne';
    public const precedence = 3;
}
