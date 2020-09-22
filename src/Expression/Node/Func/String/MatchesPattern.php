<?php

namespace Flat3\OData\Expression\Node\Func\String;

use Flat3\OData\Expression\Node\Func;

class MatchesPattern extends Func
{
    public const symbol = 'matchesPattern';
    public const arguments = 2;
}
