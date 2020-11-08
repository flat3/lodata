<?php

namespace Flat3\Lodata\Expression\Node;

use Flat3\Lodata\Expression\Event\Literal as LiteralEvent;
use Flat3\Lodata\Expression\Node;
use Flat3\Lodata\Property;

/**
 * Literal
 * @package Flat3\Lodata\Expression\Node
 */
abstract class Literal extends Node
{
    /**
     * Literal property
     * @var Property property
     * @internal
     */
    public const property = null;

    public function compute(): void
    {
        $this->expressionEvent(new LiteralEvent($this));
    }
}
