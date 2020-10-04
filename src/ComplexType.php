<?php

namespace Flat3\OData;

use Flat3\OData\Helper\ObjectArray;
use Flat3\OData\Interfaces\NamedInterface;
use Flat3\OData\Interfaces\TypeInterface;
use Flat3\OData\Traits\HasName;
use Flat3\OData\Type\Property;

class ComplexType implements NamedInterface, TypeInterface
{
    use HasName;

    /** @var ObjectArray[Property] $properties Properties */
    protected $properties;

    public function __construct($identifier)
    {
        $this->setIdentifier($identifier);
        $this->properties = new ObjectArray();
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
}
