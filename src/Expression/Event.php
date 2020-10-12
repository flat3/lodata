<?php

namespace Flat3\Lodata\Expression;

abstract class Event
{
    /** @var Node $node */
    private $node;

    public function __construct(?Node $node = null)
    {
        $this->node = $node;
    }

    public function getValue()
    {
        return $this->node->getValue();
    }

    public function getNode()
    {
        return $this->node;
    }
}
