<?php

namespace Flat3\OData\Expression\Node\Func\StringCollection;

use Flat3\OData\Expression\Node\Func;

class StartsWith extends Func
{
    public const symbol = 'startswith';
    public const arguments = 2;
}
