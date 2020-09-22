<?php

namespace Flat3\OData\Expression\Node\Operator\Arithmetic;

use Flat3\OData\Expression\Node\Operator\Arithmetic;

class Add extends Arithmetic
{
    public const symbol = 'add';
    public const precedence = 5;
}
