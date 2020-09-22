<?php

namespace Flat3\OData\Expression\Node\Func\StringCollection;

use Flat3\OData\Expression\Node\Func;

class Concat extends Func
{
    public const symbol = 'concat';
    public const arguments = 2;
}
