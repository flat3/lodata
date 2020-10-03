<?php

namespace Flat3\OData\Type;

use Flat3\OData\Interfaces\IdentifierInterface;
use Flat3\OData\Internal\ObjectArray;
use Flat3\OData\Operation;
use Flat3\OData\Property;
use Flat3\OData\Property\Declared;
use Flat3\OData\Property\Navigation;
use Flat3\OData\Traits\HasIdentifier;
use Flat3\OData\Type;

class EntityType extends Type implements IdentifierInterface
{
    use HasIdentifier;

    /** @var Property $key Primary key property */
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
        return $this->properties->get($property);
    }

    public function getNavigationProperties(): ObjectArray
    {
        return $this->properties->sliceByClass(Navigation::class);
    }

    public function getName(): string
    {
        return $this->getIdentifier();
    }
}
