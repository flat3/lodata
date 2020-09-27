<?php

namespace Flat3\OData\Option;

use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\ObjectArray;
use Flat3\OData\Option;
use Flat3\OData\Store;

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
        $selected = $this->getValue();
        $properties = $store->getEntityType()->getDeclaredProperties();

        if (!$selected || in_array('*', $selected, true)) {
            return $properties;
        }

        $result = new ObjectArray();

        foreach ($selected as $s) {
            $property = $properties[$s];

            if (null === $property) {
                throw new BadRequestException(
                    'property_does_not_exist',
                    sprintf(
                        'The requested property "%s" does not exist on this entity type',
                        $s
                    )
                );
            }

            $result[] = $properties[$s];
        }

        return $result;
    }

    public function hasValue(): bool
    {
        return !!$this->getValue();
    }

    public function getValue(): array
    {
        if ($this->value === '*') {
            return [];
        }

        return $this->getCommaSeparatedValues();
    }
}
