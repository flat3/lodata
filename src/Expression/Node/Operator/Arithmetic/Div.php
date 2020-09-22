<?php

namespace Flat3\OData\Expression\Node\Operator\Arithmetic;

use Flat3\OData\Expression\Node\Operator\Arithmetic;

class Div extends Arithmetic
{
    public const symbol = 'div';
    public const precedence = 6;
}
