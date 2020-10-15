<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\NamedInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Interfaces\TypeInterface;
use Flat3\Lodata\Traits\HasName;
use Flat3\Lodata\Type\Property;

class ComplexType implements TypeInterface, NamedInterface, ContextInterface, ResourceInterface
{
    use HasName;

    /** @var ObjectArray[Property] $properties Properties */
    protected $properties;

    public function __construct($name)
    {
        $this->setName($name);
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

    public function getDynamicProperties(): ObjectArray
    {
        return $this->properties->sliceByClass(DynamicProperty::class);
    }

    public function getProperty(string $property): ?Property
    {
        return $this->properties->get($property);
    }

    public function getNavigationProperties(): ObjectArray
    {
        return $this->properties->sliceByClass(NavigationProperty::class);
    }

    public function getContextUrl(): string
    {
        return Transaction::getContextUrl().'#'.$this->getName();
    }

    public function getResourceUrl(): string
    {
        return Transaction::getResourceUrl().$this->getName().'()';
    }
}
