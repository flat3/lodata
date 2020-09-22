<?php

namespace Flat3\OData\Expression\Node\Operator\Logical;

use Flat3\OData\Expression\Node\Operator\Logical;

class Equal extends Logical
{
    public const symbol = 'eq';
    public const precedence = 3;
}
