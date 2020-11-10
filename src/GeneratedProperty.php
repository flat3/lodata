<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Helper\PropertyValue;

/**
 * Generated Property
 * @package Flat3\Lodata
 */
abstract class GeneratedProperty extends Property
{
    /**
     * Generate the property value for this property on the provided entity
     * @param  Entity  $entity  Entity this property is generated on
     * @return PropertyValue
     */
    abstract public function invoke(Entity $entity);

    /**
     * Generate a property value for this entity
     * @param  Entity  $entity  Entity
     * @return PropertyValue Property value
     */
    public function generatePropertyValue(Entity $entity): PropertyValue
    {
        /** @var PrimitiveType $type */
        $type = $this->getType();
        $propertyValue = $entity->newPropertyValue();
        $propertyValue->setProperty($this);
        $result = $this->invoke($entity);

        if (
            !is_a($result, $type->getFactory(), true) ||
            $result === null && $type instanceof PrimitiveType && !$type->isNullable()
        ) {
            throw new InternalServerErrorException(
                'invalid_generated_property_type',
                sprintf(
                    'The generated property %s did not return a value of its defined type %s',
                    $this->getName(),
                    $type->getIdentifier()
                )
            );
        }

        $propertyValue->setValue($result);

        $entity->addProperty($propertyValue);

        return $propertyValue;
    }
}
