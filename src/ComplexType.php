<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use ArrayAccess;
use Flat3\Lodata\Annotation\Core\V1\Computed;
use Flat3\Lodata\Annotation\Core\V1\ComputedDefaultValue;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Helper\Properties;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Helper\PropertyValues;
use Flat3\Lodata\Interfaces\AnnotationInterface;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Traits\HasAnnotations;
use Flat3\Lodata\Traits\HasIdentifier;
use Flat3\Lodata\Type\Boolean;

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
     * @var Properties $properties Properties
     */
    protected $properties;

    /**
     * ComplexType constructor.
     * @param  string|Identifier  $identifier
     */
    public function __construct($identifier)
    {
        $this->setIdentifier($identifier);
        $this->properties = new Properties();
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
     * @param  Identifier|string  $name  Property name
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
     * @return Properties|DeclaredProperty[] Declared properties
     */
    public function getDeclaredProperties(): Properties
    {
        return $this->properties->sliceByClass(DeclaredProperty::class);
    }

    /**
     * Get all generated properties on this type
     * @return GeneratedProperty[]|Properties Generated properties
     */
    public function getGeneratedProperties(): Properties
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
     * @return Properties Properties
     */
    public function getProperties(): Properties
    {
        return $this->properties;
    }

    /**
     * Get all navigation properties defined on this type
     * @return Properties|NavigationProperty[] Navigation properties
     */
    public function getNavigationProperties(): Properties
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
     * @param  ComplexValue|null  $value
     * @return ComplexValue
     */
    public function instance($value = null): ComplexValue
    {
        $instance = new ComplexValue();
        $instance->setType($this);

        if (is_array($value) || $value instanceof ArrayAccess) {
            foreach ($value as $k => $v) {
                if (is_numeric($k)) {
                    continue;
                }

                $instance[$k] = $v;
            }
        }

        return $instance;
    }

    /**
     * Render this type as an OpenAPI schema
     * @return array
     */
    public function getOpenAPISchema(): array
    {
        return [
            'type' => Constants::oapiObject,
            'properties' => $this->getDeclaredProperties()->map(function (DeclaredProperty $property) {
                return $property->getOpenAPISchema();
            })
        ];
    }

    /**
     * Render this type as an OpenAPI schema for creation paths
     * @return array
     */
    public function getOpenAPICreateSchema(): array
    {
        return [
            'type' => Constants::oapiObject,
            'properties' => $this->getDeclaredProperties()->filter(function (DeclaredProperty $property) {
                return $property->getAnnotations()->sliceByClass([Computed::class])->isEmpty();
            })->map(function (DeclaredProperty $property) {
                return $property->getOpenAPISchema();
            })
        ];
    }

    /**
     * Render this type as an OpenAPI schema for update paths
     * @return array
     */
    public function getOpenAPIUpdateSchema(): array
    {
        return [
            'type' => Constants::oapiObject,
            'properties' => $this->getDeclaredProperties()->filter(function (DeclaredProperty $property) {
                return $property->getAnnotations()->sliceByClass([Computed::class])->isEmpty();
            })->map(function (DeclaredProperty $property) {
                return $property->getOpenAPISchema();
            })
        ];
    }

    /**
     * Ensure the provided property values meet the requirements of the entity type
     * @param  PropertyValues  $propertyValues  Property values being mapped into this type
     * @param  PropertyValue|null  $foreignKey  Foreign key value
     * @return void
     */
    public function assertRequiredProperties(PropertyValues $propertyValues, ?PropertyValue $foreignKey = null)
    {
        $declaredProperties = $this->getDeclaredProperties();

        foreach ($declaredProperties as $declaredProperty) {
            if ($propertyValues->exists($declaredProperty)) {
                continue;
            }

            if ($declaredProperty->hasDefaultValue()) {
                $propertyValue = new PropertyValue();
                $propertyValue->setProperty($declaredProperty);
                $propertyValue->setValue($declaredProperty->computeDefaultValue());
                $propertyValues[] = $propertyValue;
                continue;
            }

            if (
                $declaredProperty->isNullable()
                || $declaredProperty->hasAnnotation(new Computed, Boolean::true())
                || $declaredProperty->hasAnnotation(new ComputedDefaultValue, Boolean::true())
            ) {
                continue;
            }

            if ($foreignKey) {
                /** @var NavigationProperty $navigationProperty */
                $navigationProperty = $foreignKey->getProperty();

                foreach ($navigationProperty->getConstraints() as $constraint) {
                    if ($constraint->getReferencedProperty() === $declaredProperty) {
                        continue 2;
                    }
                }
            }

            $declaredProperty->assertAllowsValue(null);
        }
    }
}
