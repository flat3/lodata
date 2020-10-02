<?php

namespace Flat3\OData\Request\Option;

use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Interfaces\OrderByInterface;
use Flat3\OData\Request\Option;
use Flat3\OData\Resource\EntitySet;

/**
 * Class OrderBy
 *
 * http://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part2-url-conventions.html#sec_SystemQueryOptionorderby
 */
class OrderBy extends Option
{
    public const param = 'orderby';
    public const query_interface = OrderByInterface::class;

    public function getSortOrders(EntitySet $entitySet): array
    {
        $orders = [];

        $properties = $entitySet->getType()->getDeclaredProperties();

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
