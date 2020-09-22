<?php

namespace Flat3\OData\Expression\Node\Literal;

use Flat3\OData\Expression\Node\Literal;

class Null_ extends Literal
{
    public function getValue()
    {
        return null;
    }
}
