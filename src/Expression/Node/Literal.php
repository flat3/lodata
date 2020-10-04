<?php

namespace Flat3\OData\Expression\Node;

use Flat3\OData\Expression\Event\Literal as LiteralEvent;
use Flat3\OData\Expression\Node;
use Flat3\OData\Type\Property;

abstract class Literal extends Node
{
    /** @var \Flat3\OData\Type\EntityType\\Flat3\OData\Type\Property property */
    public const property = null;

    public function compute(): void
    {
        $this->expressionEvent(new LiteralEvent($this));
    }
}
