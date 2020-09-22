<?php

namespace Flat3\OData\Expression\Node\Func\String;

use Flat3\OData\Expression\Node\Func;

class ToUpper extends Func
{
    public const symbol = 'toupper';
    public const arguments = 1;
}
