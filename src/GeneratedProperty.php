<?php

declare(strict_types=1);

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
     * @param  ComplexValue  $value  Entity this property is generated on
     * @return PropertyValue
     */
    abstract public function invoke(ComplexValue $value);

    /**
     * Generate a property value for this entity
     * @param  ComplexValue  $value  Entity
     * @return PropertyValue Property value
     */
    public function generatePropertyValue(ComplexValue $value): PropertyValue
    {
        /** @var PrimitiveType $type */
        $type = $this->getType();
        $propertyValue = $value->newPropertyValue();
        $propertyValue->setProperty($this);
        $result = $this->invoke($value);

        if (!is_a($result, Primitive::class, true)) {
            $result = PrimitiveType::castInternalType(gettype($result))->instance($result);
        }

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

        $value->addPropertyValue($propertyValue);

        return $propertyValue;
    }
}
