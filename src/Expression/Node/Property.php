<?php

declare(strict_types=1);

namespace Flat3\Lodata\Expression\Node;

use Flat3\Lodata\Expression\Node;

/**
 * Property
 * @package Flat3\Lodata\Expression\Node
 */
class Property extends Node
{
    public function compute(): void
    {
        $this->emit($this);
    }
}
