<?php

namespace Flat3\OData\Expression\Node\Operator\Arithmetic;

use Flat3\OData\Expression\Node\Operator\Arithmetic;

class Mod extends Arithmetic
{
    public const symbol = 'mod';
    public const precedence = 6;
}
