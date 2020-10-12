<?php

namespace Flat3\Lodata\Expression\Node;

use Flat3\Lodata\Expression\Event\Literal as LiteralEvent;
use Flat3\Lodata\Expression\Node;
use Flat3\Lodata\Type\Property;

abstract class Literal extends Node
{
    /** @var Property property */
    public const property = null;

    public function compute(): void
    {
        $this->expressionEvent(new LiteralEvent($this));
    }
}
