<?php

namespace Flat3\OData\Expression\Node;

use Flat3\OData\Expression\Operator;

abstract class Group extends Operator
{
    /**
     * Grouping operators do not output anything during compute
     */
    public function compute(): void
    {
    }
}
