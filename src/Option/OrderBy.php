<?php

namespace Flat3\OData\Option;

use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Option;

/**
 * Class OrderBy
 *
 * http://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part2-url-conventions.html#sec_SystemQueryOptionorderby
 */
class OrderBy extends Option
{
    public const param = 'orderby';

    public function getValue(): array
    {
        $orders = [];

        foreach ($this->value as $expression) {
            [$literal, $direction] = array_map('trim', explode(' ', $expression));

            if (!$direction) {
                $direction = 'asc';
            }

            $direction = strtolower($direction);

            if (!in_array($direction, ['asc', 'desc'], true)) {
                throw new BadRequestException('invalid_orderby', 'The orderby direction must be "asc" or "desc"');
            }

            $orders[] = [$literal, $direction];
        }

        return $orders;
    }
}
