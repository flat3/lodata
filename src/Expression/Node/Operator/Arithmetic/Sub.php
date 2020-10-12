<?php

namespace Flat3\Lodata\Expression\Node\Operator\Arithmetic;

use Flat3\Lodata\Expression\Node\Operator\Arithmetic;

class Sub extends Arithmetic
{
    public const symbol = 'sub';
    public const precedence = 5;
}
