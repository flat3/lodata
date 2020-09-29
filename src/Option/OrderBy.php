<?php

namespace Flat3\OData\Option;

use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Option;
use Flat3\OData\Store;

/**
 * Class OrderBy
 *
 * http://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part2-url-conventions.html#sec_SystemQueryOptionorderby
 */
class OrderBy extends Option
{
    public const param = 'orderby';

    public function getSortOrders(Store $store): array
    {
        $orders = [];

        $properties = $store->getEntityType()->getDeclaredProperties();

        foreach ($this->getCommaSeparatedValues() as $expression) {
            $pair = array_map('trim', explode(' ', $expression));

            $literal = array_shift($pair);
            $direction = array_shift($pair) ?? 'asc';

            if ($pair) {
                throw new BadRequestException('invalid_orderby_syntax', 'The requested orderby syntax is invalid');
            }

            $direction = strtolower($direction);

            if (!in_array($direction, ['asc', 'desc'], true)) {
                throw new BadRequestException(
                    'invalid_orderby_direction',
                    'The orderby direction must be "asc" or "desc"'
                );
            }

            if (!$properties->get($literal)) {
                throw new BadRequestException(
                    'invalid_orderby_property',
                    sprintf('The provided property %s was not found in this entity type', $literal)
                );
            }

            $orders[] = [$literal, $direction];
        }

        return $orders;
    }
}
