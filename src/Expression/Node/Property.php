<?php

namespace Flat3\Lodata\Expression\Node;

use Flat3\Lodata\Expression\Event\Property as PropertyEvent;
use Flat3\Lodata\Expression\Node;

/**
 * Property
 * @package Flat3\Lodata\Expression\Node
 */
class Property extends Node
{
    public function compute(): void
    {
        $this->expressionEvent(new PropertyEvent($this));
    }
}
