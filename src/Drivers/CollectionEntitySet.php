<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Entity;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\EntitySet\CreateInterface;
use Flat3\Lodata\Interfaces\EntitySet\DeleteInterface;
use Flat3\Lodata\Interfaces\EntitySet\UpdateInterface;
use Illuminate\Support\Collection;

/**
 * Class CollectionEntitySet
 * @package Flat3\Lodata\Drivers
 */
class CollectionEntitySet extends EnumerableEntitySet implements CreateInterface, DeleteInterface, UpdateInterface
{
    /**
     * Set the collection for this entity set
     * @param  Collection  $collection  Collection
     * @return $this
     */
    public function setCollection(Collection $collection): self
    {
        $this->enumerable = $collection;

        return $this;
    }

    /**
     * Get the collection for this entity set
     * @return Collection Collection
     */
    public function getCollection(): Collection
    {
        return $this->enumerable;
    }

    /**
     * Create a new entity
     * @return Entity
     */
    public function create(): Entity
    {
        $entity = $this->newEntity();
        $body = $this->transaction->getBody();
        $entity->fromArray($body);
        $entityId = $entity->getEntityId();

        if ($entityId) {
            $key = $entityId->getPrimitiveValue()->get();
            $this->enumerable[$key] = $entity->toArray();
        } else {
            $this->enumerable[] = $entity->toArray();
            $entity->setEntityId($this->enumerable->count() - 1);
        }

        return $entity;
    }

    /**
     * Delete an entity
     * @param  PropertyValue  $key
     */
    public function delete(PropertyValue $key): void
    {
        $this->enumerable->forget($key->getPrimitiveValue()->get());
    }

    /**
     * Update an entity
     * @param  PropertyValue  $key
     * @return Entity
     */
    public function update(PropertyValue $key): Entity
    {
        $entity = $this->read($key);
        $body = $this->transaction->getBody();
        $entity->fromArray($body);
        $item = $entity->toArray();
        unset($item['id']);

        $this->enumerable[$key->getPrimitiveValue()->get()] = $item;

        return $this->read($key);
    }
}