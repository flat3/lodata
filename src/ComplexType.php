<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use Flat3\Lodata\Annotation\Core\V1\Computed;
use Flat3\Lodata\Annotation\Core\V1\Immutable;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Interfaces\AnnotationInterface;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Traits\HasAnnotations;
use Flat3\Lodata\Traits\HasIdentifier;

/**
 * Complex Type
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530372
 * @package Flat3\Lodata
 */
class ComplexType extends Type implements ResourceInterface, ContextInterface, IdentifierInterface, AnnotationInterface
{
    const identifier = 'Edm.ComplexType';

    use HasAnnotations;
    use HasIdentifier;

    /**
     * @var ObjectArray $properties Properties
     */
    protected $properties;

    /**
     * ComplexType constructor.
     * @param  string|Identifier  $identifier
     */
    public function __construct($identifier)
    {
        $this->setIdentifier($identifier);
        $this->properties = new ObjectArray();
    }

    /**
     * Generate a new complex type
     * @param  string|Identifier  $identifier
     * @return ComplexType Complex Type
     * @codeCoverageIgnore
     */
    public static function factory($identifier)
    {
        return new self($identifier);
    }

    /**
     * Add a property
     * @param  Property  $property  The property to add
     * @return $this
     */
    public function addProperty(Property $property): self
    {
        $this->properties[] = $property;

        return $this;
    }

    /**
     * Drop a property
     * @param  mixed  $property  The property to drop
     * @return $this
     */
    public function dropProperty($property): self
    {
        $this->properties->drop($property);

        return $this;
    }

    /**
     * Create and add a declared property
     * @param  mixed  $name  Property name
     * @param  Type  $type  Property type
     * @return $this
     */
    public function addDeclaredProperty($name, Type $type): self
    {
        $this->addProperty(new DeclaredProperty($name, $type));
        return $this;
    }

    /**
     * Get all declared properties on this type
     * @return ObjectArray|DeclaredProperty[] Declared properties
     */
    public function getDeclaredProperties(): ObjectArray
    {
        return $this->properties->sliceByClass(DeclaredProperty::class);
    }

    /**
     * Get all generated properties on this type
     * @return ObjectArray Generated properties
     */
    public function getGeneratedProperties(): ObjectArray
    {
        return $this->properties->sliceByClass(GeneratedProperty::class);
    }

    /**
     * Get a property by name from this type
     * @param  string  $property
     * @return Property|null Property
     */
    public function getProperty(string $property): ?Property
    {
        return $this->properties->get($property);
    }

    /**
     * Get a declared property by name from this type
     * @param  string  $property
     * @return DeclaredProperty|null Declared property
     */
    public function getDeclaredProperty(string $property): ?DeclaredProperty
    {
        $property = $this->properties->get($property);

        return $property instanceof DeclaredProperty ? $property : null;
    }

    /**
     * Get a navigation property by name from this type
     * @param  string  $property
     * @return NavigationProperty|null Navigation property
     */
    public function getNavigationProperty(string $property): ?NavigationProperty
    {
        $property = $this->properties->get($property);

        return $property instanceof NavigationProperty ? $property : null;
    }

    /**
     * Get a generated property by name from this type
     * @param  string  $property
     * @return GeneratedProperty|null Generated property
     */
    public function getGeneratedProperty(string $property): ?GeneratedProperty
    {
        $property = $this->properties->get($property);

        return $property instanceof GeneratedProperty ? $property : null;
    }

    /**
     * Get all properties defined on this type
     * @return ObjectArray Properties
     */
    public function getProperties(): ObjectArray
    {
        return $this->properties;
    }

    /**
     * Get all navigation properties defined on this type
     * @return ObjectArray|NavigationProperty[] Navigation properties
     */
    public function getNavigationProperties(): ObjectArray
    {
        return $this->properties->sliceByClass(NavigationProperty::class);
    }

    /**
     * Get the context URL for this type
     * @param  Transaction  $transaction  Related transaction
     * @return string Context URL
     */
    public function getContextUrl(Transaction $transaction): string
    {
        return $transaction->getContextUrl().'#'.$this->getIdentifier();
    }

    /**
     * Get the resource URL for this type
     * @param  Transaction  $transaction  Related transaction
     * @return string Resource URL
     */
    public function getResourceUrl(Transaction $transaction): string
    {
        return $transaction->getResourceUrl().$this->getName().'()';
    }

    /**
     * Generate an instance of a complex type
     * @param  null  $value
     * @return ObjectArray
     */
    public function instance($value = null)
    {
        return new ObjectArray();
    }

    /**
     * Render this type as an OpenAPI schema
     * @return array
     */
    public function toOpenAPISchema(): array
    {
        return [
            'type' => Constants::oapiObject,
            'properties' => $this->getDeclaredProperties()->map(function (DeclaredProperty $property) {
                return $property->getType()->toOpenAPISchema();
            })
        ];
    }

    /**
     * Render this type as an OpenAPI schema for creation paths
     * @return array
     */
    public function toOpenAPICreateSchema(): array
    {
        return [
            'type' => Constants::oapiObject,
            'properties' => $this->getDeclaredProperties()->filter(function (DeclaredProperty $property) {
                return !$property->getAnnotations()->sliceByClass(Computed::class)->hasEntries();
            })->map(function (DeclaredProperty $property) {
                return $property->getType()->toOpenAPISchema();
            })
        ];
    }

    /**
     * Render this type as an OpenAPI schema for update paths
     * @return array
     */
    public function toOpenAPIUpdateSchema(): array
    {
        return [
            'type' => Constants::oapiObject,
            'properties' => $this->getDeclaredProperties()->filter(function (DeclaredProperty $property) {
                return !$property->getAnnotations()->sliceByClass([Computed::class, Immutable::class])->hasEntries();
            })->map(function (DeclaredProperty $property) {
                return $property->getType()->toOpenAPISchema();
            })
        ];
    }
}
