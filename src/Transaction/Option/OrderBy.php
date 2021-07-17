<?php

declare(strict_types=1);

namespace Flat3\Lodata\Transaction\Option;

use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Transaction\Option;

/**
 * OrderBy
 * @link https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part2-url-conventions.html#sec_SystemQueryOptionorderby
 * @package Flat3\Lodata\Transaction\Option
 */
class OrderBy extends Option
{
    public const param = 'orderby';
    public const asc = 'asc';
    public const desc = 'desc';

    public function getSortOrders(): array
    {
        $orders = [];

        foreach ($this->getCommaSeparatedValues() as $expression) {
            $pair = array_map('trim', explode(' ', $expression));

            $literal = array_shift($pair);
            $direction = array_shift($pair) ?? self::asc;

            if ($pair) {
                throw new BadRequestException('invalid_orderby_syntax', 'The requested orderby syntax is invalid');
            }

            $direction = strtolower($direction);

            if (!in_array($direction, [self::asc, self::desc], true)) {
                throw new BadRequestException(
                    'invalid_orderby_direction',
                    'The orderby direction must be "asc" or "desc"'
                );
            }

            $orders[] = [$literal, $direction];
        }

        return $orders;
    }
}
