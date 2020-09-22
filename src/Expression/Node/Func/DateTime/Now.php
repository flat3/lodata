<?php

namespace Flat3\OData\Expression\Node\Func\DateTime;

use Flat3\OData\Expression\Node\Func;

class Now extends Func
{
    public const symbol = 'now';
    public const arguments = 0;
}
