<?php

declare(strict_types=1);

namespace Flat3\Lodata\Traits;

use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\NavigationBinding;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\ReferentialConstraint;

trait HasNavigation
{
    /**
     * Navigation bindings
     * @var ObjectArray $navigationBindings
     */
    protected $navigationBindings;

    /**
     * The navigation property value that relates to this target
     * @var PropertyValue $navigationPropertyValue
     */
    protected $navigationPropertyValue;

    /**
     * Add a navigation binding
     * @param  NavigationBinding  $binding  Navigation binding
     * @return $this
     */
    public function addNavigationBinding(NavigationBinding $binding): self
    {
        $this->navigationBindings[] = $binding;

        return $this;
    }

    /**
     * Get the navigation bindings
     * @return ObjectArray|NavigationBinding[]
     */
    public function getNavigationBindings(): ObjectArray
    {
        return $this->navigationBindings;
    }

    /**
     * Get the navigation binding for the provided navigation property on this target
     * @param  NavigationProperty  $property  Navigation property
     * @return NavigationBinding|null Navigation binding
     */
    public function getBindingByNavigationProperty(NavigationProperty $property): ?NavigationBinding
    {
        /** @var NavigationBinding $navigationBinding */
        foreach ($this->navigationBindings as $navigationBinding) {
            if ($navigationBinding->getPath() === $property) {
                return $navigationBinding;
            }
        }

        return null;
    }

    /**
     * Get the navigation property value that relates to this target
     * @param  PropertyValue  $property  Navigation property value
     * @return $this
     */
    public function setNavigationPropertyValue(PropertyValue $property): self
    {
        $this->navigationPropertyValue = $property;

        return $this;
    }

    /**
     * Get the entity ID of the entity this target was generated from using the attached expansion property value
     * @return PropertyValue Entity ID
     */
    public function resolveExpansionKey(): PropertyValue
    {
        /** @var NavigationProperty $navigationProperty */
        $navigationProperty = $this->navigationPropertyValue->getProperty();
        $sourceEntity = $this->navigationPropertyValue->getParent();

        $targetConstraint = null;
        /** @var ReferentialConstraint $constraint */
        foreach ($navigationProperty->getConstraints() as $constraint) {
            if ($this->getType()->getProperty($constraint->getReferencedProperty()->getName()) && $sourceEntity->getEntitySet()->getType()->getProperty($constraint->getProperty()->getName())) {
                $targetConstraint = $constraint;
                break;
            }
        }

        if (!$targetConstraint) {
            throw new BadRequestException(
                'no_expansion_constraint',
                sprintf(
                    'No applicable constraint could be found between sets %s and %s for expansion',
                    $sourceEntity->getEntitySet()->getIdentifier(),
                    $this->getIdentifier()
                )
            );
        }

        /** @var PropertyValue $keyPropertyValue */
        $keyPropertyValue = $sourceEntity->getPropertyValues()->get($targetConstraint->getProperty());
        if ($keyPropertyValue->getPrimitiveValue() === null) {
            throw new InternalServerErrorException('missing_expansion_key', 'The target constraint key is null');
        }

        $referencedProperty = $targetConstraint->getReferencedProperty();

        $targetKey = new PropertyValue();
        $targetKey->setProperty($referencedProperty);
        $targetKey->setValue($keyPropertyValue->getValue());

        return $targetKey;
    }
}