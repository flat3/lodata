<?php

namespace Flat3\OData;

class Primitive
{
    /** @var Entity $entity */
    private $entity;

    /** @var Property $property */
    private $property;

    /** @var Type $value */
    private $value;

    public function __construct($value, Property $property, ?Entity $entity = null)
    {
        $this->property = $property;

        if ($value instanceof Primitive) {
            $value = $value->getInternalValue();
        }

        $this->value = $property->getType()::factory($value);
        $this->entity = $entity;
    }

    public function getInternalValue()
    {
        return $this->value->getInternalValue();
    }

    public function setEntity(Entity $entity): void
    {
        $this->entity = $entity;
    }

    public function getEntity(): Entity
    {
        return $this->entity;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function getValue(): Type
    {
        return $this->value;
    }

    public function toUrl(): string
    {
        return $this->value->toUrl();
    }

    public function toJsonIeee754(): ?string
    {
        return $this->value->toJsonIeee754();
    }

    public function toJson()
    {
        return $this->value->toJson();
    }
}
