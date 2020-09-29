<?php

namespace Flat3\OData\Option;

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
        $declaredProperties = $store->getEntityType()->getDeclaredProperties();

        if (!$selected || in_array('*', $selected, true)) {
            return $declaredProperties;
        }

        $properties = new ObjectArray();

        foreach ($selected as $s) {
            $property = $store->getTypeProperty($s);

            if (null === $property) {
                continue;
            }

            $properties[] = $property;
        }

        return $properties;
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
