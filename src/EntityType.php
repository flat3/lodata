<?php

namespace Flat3\OData;

use Flat3\OData\Property\Navigation;

abstract class EntityType extends Resource
{
    /** @var Property $key Primary key property */
    protected $key;

    /** @var ObjectArray[Property] $declared_properties Declared properties */
    protected $declaredProperties;

    /** @var ObjectArray[Property] $navigation_properties Navigation properties */
    protected $navigationProperties;

    /** @var ObjectArray[Operation] $bound_operations Operations bound to this entity type */
    protected $boundOperations;

    /** @var DataModel $dataModel */
    protected $dataModel;

    public function __construct($identifier)
    {
        parent::__construct($identifier);

        $this->declaredProperties = new ObjectArray();
        $this->navigationProperties = new ObjectArray();
        $this->boundOperations = new ObjectArray();
    }

    public function add_bound_operation(Operation $operation): self
    {
        $this->boundOperations[] = $operation;

        return $this;
    }

    public function getBoundOperations(): ObjectArray
    {
        return $this->boundOperations;
    }

    /**
     * Return the defined key
     *
     * @return Property|null
     */
    public function getKey(): ?Property
    {
        return $this->key;
    }

    /**
     * Set the key property by name
     *
     * @param  Property  $key
     *
     * @return $this
     */
    public function setKey(Property $key): self
    {
        $this->addProperty($key);

        // Key property is not nullable
        $key->setNullable(false);

        // Key property should be marked keyable
        $key->setAlternativeKey(true);

        $this->key = $key;

        return $this;
    }

    /**
     * Add a property to the list
     *
     * @param  Property  $property
     *
     * @return $this
     */
    public function addProperty(Property $property): self
    {
        switch (true) {
            case $property instanceof Navigation:
                $this->navigationProperties[] = $property;
                break;

            default:
                $this->declaredProperties[] = $property;
                break;
        }

        return $this;
    }

    public function getDeclaredProperties(): ObjectArray
    {
        return $this->declaredProperties;
    }

    public function getProperty(string $property): ?Property
    {
        return $this->getProperties()->get($property);
    }

    public function getProperties(): ObjectArray
    {
        return ObjectArray::merge($this->declaredProperties, $this->navigationProperties);
    }

    public function getNavigationProperties(): ObjectArray
    {
        return $this->navigationProperties;
    }

    public function getEdmType(): string
    {
        return (string) $this->getIdentifier();
    }

    public function getDataModel(): DataModel
    {
        return $this->dataModel;
    }

    public function setDataModel(DataModel $dataModel): self
    {
        $this->dataModel = $dataModel;

        return $this;
    }
}
