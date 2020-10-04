<?php

namespace Flat3\OData;

class NavigationBinding
{
    /** @var NavigationProperty $path */
    private $path;

    /** @var \Flat3\OData\\Flat3\OData\EntitySet $target */
    private $target;

    public function __construct(NavigationProperty $path, EntitySet $target)
    {
        $this->path = $path;
        $this->target = $target;
    }

    public function getPath(): NavigationProperty
    {
        return $this->path;
    }

    public function getTarget(): EntitySet
    {
        return $this->target;
    }

    public function __toString()
    {
        return $this->path->getName().'/'.$this->target->getName();
    }
}
