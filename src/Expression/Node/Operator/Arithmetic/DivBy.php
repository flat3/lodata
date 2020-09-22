<?php

namespace Flat3\OData\Expression\Node\Operator\Arithmetic;

use Flat3\OData\Expression\Node\Operator\Arithmetic;

class DivBy extends Arithmetic
{
    public const symbol = 'divby';
    public const precedence = 6;
}
