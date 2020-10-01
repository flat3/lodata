<?php

namespace Flat3\OData\Property\Navigation;

use Flat3\OData\Property\Navigation;
use Flat3\OData\Resource\Store;

class Binding
{
    /** @var Navigation $path */
    private $path;

    /** @var Store $target */
    private $target;

    public function __construct(Navigation $path, Store $target)
    {
        $this->path = $path;
        $this->target = $target;
    }

    public function getPath(): Navigation
    {
        return $this->path;
    }

    public function getTarget(): Store
    {
        return $this->target;
    }

    public function __toString()
    {
        return $this->path->getIdentifier()->get().'/'.$this->target->getIdentifier()->get();
    }
}
