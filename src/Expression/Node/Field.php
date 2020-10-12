<?php

namespace Flat3\Lodata\Expression\Node;

use Flat3\Lodata\Expression\Event\Field as FieldEvent;
use Flat3\Lodata\Expression\Node;

class Field extends Node
{
    public function compute(): void
    {
        $this->expressionEvent(new FieldEvent($this));
    }
}
