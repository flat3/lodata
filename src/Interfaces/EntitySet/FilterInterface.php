<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces\EntitySet;

use Flat3\Lodata\Expression\Node;

/**
 * Filter Interface
 * @package Flat3\Lodata\Interfaces\EntitySet
 */
interface FilterInterface
{
    /**
     * Handle a discovered expression symbol in the filter query
     * @param  Node  $node  Node
     * @return bool True if the node was handled
     */
    public function filter(Node $node): ?bool;
}