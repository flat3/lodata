<?php

namespace Flat3\OData\Expression\Node\Func\StringCollection;

use Flat3\OData\Expression\Node\Func;

class Contains extends Func
{
    public const symbol = 'contains';
    public const arguments = 2;
}
