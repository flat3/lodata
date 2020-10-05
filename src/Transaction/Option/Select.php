<?php

namespace Flat3\OData\Transaction\Option;

use Flat3\OData\EntitySet;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Helper\ObjectArray;
use Flat3\OData\Transaction\Option;

/**
 * Class Select
 *
 * https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_SystemQueryOptionselect
 */
class Select extends Option
{
    public const param = 'select';

    public function getSelectedProperties(EntitySet $entitySet): ObjectArray
    {
        $declaredProperties = $entitySet->getType()->getDeclaredProperties();

        if ($this->isStar()) {
            return $declaredProperties;
        }

        if (!$this->hasValue()) {
            return $declaredProperties;
        }

        $properties = new ObjectArray();
        $selectedProperties = $this->getCommaSeparatedValues();

        foreach ($selectedProperties as $selectedProperty) {
            $property = $entitySet->getType()->getProperty($selectedProperty);

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
}
