<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers;

use Illuminate\Support\LazyCollection;

class LazyCollectionEntitySet extends EnumerableEntitySet
{
    /**
     * Set the LazyCollection for this entity set
     * @param  LazyCollection  $collection
     * @return $this
     */
    public function setCollection(LazyCollection $collection): self
    {
        $this->enumerable = $collection;

        return $this;
    }

    /**
     * Get the LazyCollection for this entity set
     * @return LazyCollection
     */
    public function getCollection(): LazyCollection
    {
        return $this->enumerable;
    }
}