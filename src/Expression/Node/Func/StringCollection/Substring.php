<?php

namespace Flat3\Lodata\Expression\Node\Func\StringCollection;

use Flat3\Lodata\Expression\Node\Func;

class Substring extends Func
{
    public const symbol = 'substring';
    public const arguments = [2, 3];
}
