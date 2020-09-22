<?php

namespace Flat3\OData\Expression\Node\Operator\Comparison;

use Flat3\OData\Expression\Node\Operator\Comparison;

class And_ extends Comparison
{
    public const symbol = 'and';
    public const precedence = 2;
}
