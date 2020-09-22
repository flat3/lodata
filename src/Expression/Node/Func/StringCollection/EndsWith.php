<?php

namespace Flat3\OData\Expression\Node\Func\StringCollection;

use Flat3\OData\Expression\Node\Func;

class EndsWith extends Func
{
    public const symbol = 'endswith';
    public const arguments = 2;
}
