<?php

namespace Flat3\Lodata\Expression\Node;

use Flat3\Lodata\Expression\Operator;

abstract class Group extends Operator
{
    /**
     * Grouping operators do not output anything during compute
     */
    public function compute(): void
    {
    }
}
