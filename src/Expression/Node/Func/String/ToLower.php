<?php

namespace Flat3\OData\Expression\Node\Func\String;

use Flat3\OData\Expression\Node\Func;

class ToLower extends Func
{
    public const symbol = 'tolower';
    public const arguments = 1;
}
