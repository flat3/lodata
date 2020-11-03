<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Traits\HasIdentifier;

class ComplexType extends Type implements ResourceInterface, ContextInterface, IdentifierInterface
{
    use HasIdentifier;

    /** @var ObjectArray[Property] $properties Properties */
    protected $properties;

    public function __construct($identifier)
    {
        $this->setIdentifier($identifier);
        $this->properties = new ObjectArray();
    }

    public static function factory($identifier): self
    {
        return new static($identifier);
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

    public function dropProperty($property): self
    {
        $this->properties->drop($property);

        return $this;
    }

    public function addDeclaredProperty($name, Type $type): self
    {
        $this->addProperty(new DeclaredProperty($name, $type));
        return $this;
    }

    public function getDeclaredProperties(): ObjectArray
    {
        return $this->properties->sliceByClass(DeclaredProperty::class);
    }

    public function getGeneratedProperties(): ObjectArray
    {
        return $this->properties->sliceByClass(GeneratedProperty::class);
    }

    public function getProperty(string $property): ?Property
    {
        return $this->properties->get($property);
    }

    public function getDeclaredProperty(string $property): ?DeclaredProperty
    {
        $property = $this->properties->get($property);

        return $property instanceof DeclaredProperty ? $property : null;
    }

    public function getNavigationProperty(string $property): ?NavigationProperty
    {
        $property = $this->properties->get($property);

        return $property instanceof NavigationProperty ? $property : null;
    }

    public function getGeneratedProperty(string $property): ?GeneratedProperty
    {
        $property = $this->properties->get($property);

        return $property instanceof GeneratedProperty ? $property : null;
    }

    public function getProperties(): ObjectArray
    {
        return $this->properties;
    }

    public function getNavigationProperties(): ObjectArray
    {
        return $this->properties->sliceByClass(NavigationProperty::class);
    }

    public function getContextUrl(Transaction $transaction): string
    {
        return $transaction->getContextUrl().'#'.$this->getIdentifier();
    }

    public function getResourceUrl(Transaction $transaction): string
    {
        return $transaction->getResourceUrl().$this->getName().'()';
    }

    public function instance($value = null)
    {
        return new ObjectArray();
    }
}
