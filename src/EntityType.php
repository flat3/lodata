<?php

namespace Flat3\OData;

use Flat3\OData\Helper\ObjectArray;
use Flat3\OData\Interfaces\IdentifierInterface;
use Flat3\OData\Traits\HasIdentifier;
use Flat3\OData\Type\Property;

class EntityType extends Type implements IdentifierInterface
{
    use HasIdentifier;

    /** @var \Flat3\OData\Type\Property $key Primary key property */
    protected $key;

    /** @var ObjectArray[Property] $properties Properties */
    protected $properties;

    public function __construct($identifier)
    {
        $this->setIdentifier($identifier);
        $this->properties = new ObjectArray();
    }

    /**
     * Return the defined key
     *
     * @return \Flat3\OData\Type\EntityType\\Flat3\OData\Type\Property|null
     */
    public function getKey(): ?Property
    {
        return $this->key;
    }

    /**
     * Set the key property by name
     *
     * @param  \Flat3\OData\Type\Property  $key
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
     * @param  \Flat3\OData\Type\Property  $property
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
        return $this->properties->sliceByClass(DeclaredProperty::class);
    }

    public function getProperty(string $property): ?Property
    {
        return $this->properties->get($property);
    }

    public function getNavigationProperties(): ObjectArray
    {
        return $this->properties->sliceByClass(NavigationProperty::class);
    }

    public function getName(): string
    {
        return $this->getIdentifier();
    }
}
