<?php

namespace Flat3\OData\Request\Option;

use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Internal\ObjectArray;
use Flat3\OData\Request\Option;
use Flat3\OData\Resource\Store;

/**
 * Class Select
 *
 * https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_SystemQueryOptionselect
 */
class Select extends Option
{
    public const param = 'select';

    public function getSelectedProperties(Store $store): ObjectArray
    {
        $declaredProperties = $store->getType()->getDeclaredProperties();

        if ($this->isStar()) {
            return $declaredProperties;
        }

        if (!$this->hasValue()) {
            return $declaredProperties;
        }

        $properties = new ObjectArray();
        $selectedProperties = $this->getValue();

        foreach ($selectedProperties as $selectedProperty) {
            $property = $store->getTypeProperty($selectedProperty);

            if (null === $property) {
                throw new BadRequestException(
                    'property_does_not_exist',
                    sprintf(
                        'The requested property "%s" does not exist on this entity type',
                        $selectedProperty
                    )
                );
            }

            $properties[] = $property;
        }

        return $properties;
    }

    public function isStar(): bool
    {
        return $this->value === '*';
    }

    public function getValue(): array
    {
        return $this->getCommaSeparatedValues();
    }
}
