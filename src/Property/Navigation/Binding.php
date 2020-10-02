<?php

namespace Flat3\OData\Property\Navigation;

use Flat3\OData\EntitySet;
use Flat3\OData\Property\Navigation;

class Binding
{
    /** @var Navigation $path */
    private $path;

    /** @var \Flat3\OData\EntitySet $target */
    private $target;

    public function __construct(Navigation $path, EntitySet $target)
    {
        $this->path = $path;
        $this->target = $target;
    }

    public function getPath(): Navigation
    {
        return $this->path;
    }

    public function getTarget(): EntitySet
    {
        return $this->target;
    }

    public function __toString()
    {
        return $this->path->getIdentifier()->get().'/'.$this->target->getIdentifier()->get();
    }
}
