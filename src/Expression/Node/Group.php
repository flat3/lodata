<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node;

use Flat3\Lodata\Expression\Operator;

/**
 * Group
 * @package Flat3\Lodata\Expression\Node
 */
abstract class Group extends Operator
{
    /**
     * Grouping operators do not output anything during compute
     */
    public function compute(): void
    {
    }
}
