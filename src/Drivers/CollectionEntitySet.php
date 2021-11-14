<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers;

use Flat3\Lodata\Annotation\Core\V1\PositionalInsert;
use Flat3\Lodata\ComplexValue;
use Flat3\Lodata\Entity;
use Flat3\Lodata\Helper\Annotations;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Helper\PropertyValues;
use Flat3\Lodata\Interfaces\EntitySet\CreateInterface;
use Flat3\Lodata\Interfaces\EntitySet\DeleteInterface;
use Flat3\Lodata\Interfaces\EntitySet\UpdateInterface;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Type\Numeric;
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
     * Return whether this collection is numerically indexed
     * @return bool
     */
    public function isNumericallyIndexed(): bool
    {
        return $this->getType()->getKey()->getType()->instance() instanceof Numeric;
    }

    /**
     * Create a new entity
     * @param  PropertyValues  $propertyValues  Property values
     * @return Entity
     */
    public function create(PropertyValues $propertyValues): Entity
    {
        $entity = $this->newEntity();

        foreach ($propertyValues as $propertyValue) {
            $entity[$propertyValue->getProperty()->getName()] = $propertyValue->getValue();
        }

        $entityId = $entity->getEntityId();
        $index = $this->getIndex();

        $item = $entity->toArray();

        switch (true) {
            case !!$entityId:
                $key = $entityId->getPrimitiveValue();
                $this->enumerable[$key] = $item;
                break;

            case $index->hasValue() && $this->isNumericallyIndexed():
                $this->enumerable->splice($index->getValue(), 0, [$item]);
                $entity->setEntityId($index->getValue());
                break;

            default:
                $this->enumerable[] = $item;
                $entity->setEntityId($this->enumerable->count() - 1);
                break;
        }

        return $entity;
    }

    /**
     * Delete an entity
     * @param  PropertyValue  $key
     */
    public function delete(PropertyValue $key): void
    {
        $this->enumerable->forget($key->getPrimitiveValue());
    }

    /**
     * Update an entity
     * @param  PropertyValue  $key
     * @param  PropertyValue[]|PropertyValues  $propertyValues  Property values
     * @return Entity
     */
    public function update(PropertyValue $key, PropertyValues $propertyValues): Entity
    {
        $entity = $this->read($key);

        foreach ($propertyValues as $propertyValue) {
            $property = $propertyValue->getProperty();
            $propertyName = $property->getName();
            $propertyValue = $propertyValue->getValue();

            switch (true) {
                case $propertyValue instanceof ComplexValue:
                    $entity[$propertyName] = $propertyValue->toArray();
                    break;

                case $propertyValue instanceof Primitive:
                    $entity[$propertyName] = $propertyValue->get();
                    break;
            }
        }

        $item = $entity->toArray();
        unset($item['id']);

        $this->enumerable[$key->getPrimitiveValue()] = $item;

        return $this->read($key);
    }

    public function getAnnotations(): Annotations
    {
        $annotations = parent::getAnnotations();

        if ($this->isNumericallyIndexed()) {
            $annotations->set(new PositionalInsert());
        }

        return $annotations;
    }
}