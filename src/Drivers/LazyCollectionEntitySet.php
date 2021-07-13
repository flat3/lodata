<?php

namespace Flat3\Lodata\Drivers;

use Illuminate\Support\LazyCollection;

class LazyCollectionEntitySet extends EnumerableEntitySet
{
    public function setCollection(LazyCollection $collection): self
    {
        $this->enumerable = $collection;

        return $this;
    }

    public function getCollection(): LazyCollection
    {
        return $this->enumerable;
    }
}