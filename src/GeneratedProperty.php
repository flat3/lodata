<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Helper\PropertyValue;

abstract class GeneratedProperty extends Property
{
    abstract public function invoke(Entity $entity);

    public function generatePropertyValue(Entity $entity): PropertyValue
    {
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
