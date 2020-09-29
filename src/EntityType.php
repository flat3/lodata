<?php

namespace Flat3\OData;

use Flat3\OData\Property\Declared;
use Flat3\OData\Property\Navigation;

abstract class EntityType extends Resource
{
    /** @var Property $key Primary key property */
    protected $key;

    /** @var ObjectArray[Property] $properties Properties */
    protected $properties;

    /** @var ObjectArray[Operation] $bound_operations Operations bound to this entity type */
    protected $boundOperations;

    /** @var DataModel $dataModel */
    protected $dataModel;

    public function __construct($identifier)
    {
        parent::__construct($identifier);

        $this->properties = new ObjectArray();
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
        $this->properties[] = $property;

        return $this;
    }

    public function getDeclaredProperties(): ObjectArray
    {
        return $this->properties->sliceByClass(Declared::class);
    }

    public function getProperty(string $property): ?Property
    {
        return $this->getProperties()->get($property);
    }

    public function getProperties(): ObjectArray
    {
        return $this->properties;
    }

    public function getNavigationProperties(): ObjectArray
    {
        return $this->properties->sliceByClass(Navigation::class);
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
