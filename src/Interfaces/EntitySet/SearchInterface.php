<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces\EntitySet;

use Flat3\Lodata\Expression\Node;

/**
 * Search Interface
 * @package Flat3\Lodata\Interfaces\EntitySet
 */
interface SearchInterface
{
    /**
     * Handle a discovered expression symbol in the search query
     * @param  Node  $node  Node
     * @return bool True if the node was handled
     */
    public function search(Node $node): ?bool;
}