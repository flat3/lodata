<?php

namespace Flat3\OData\Expression\Node\Operator\Arithmetic;

use Flat3\OData\Expression\Node\Operator\Arithmetic;

class Sub extends Arithmetic
{
    public const symbol = 'sub';
    public const precedence = 5;
}
