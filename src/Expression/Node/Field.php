<?php

namespace Flat3\OData\Expression\Node;

use Flat3\OData\Expression\Event\Field as FieldEvent;
use Flat3\OData\Expression\Node;

class Field extends Node
{
    public function compute(): void
    {
        $this->expressionEvent(new FieldEvent($this));
    }
}
