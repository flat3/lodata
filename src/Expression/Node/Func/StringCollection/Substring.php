<?php

namespace Flat3\OData\Expression\Node\Func\StringCollection;

use Flat3\OData\Expression\Node\Func;

class Substring extends Func
{
    public const symbol = 'substring';
    public const arguments = [2, 3];
}
