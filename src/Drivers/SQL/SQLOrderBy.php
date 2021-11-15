<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\Exception\Protocol\BadRequestException;

/**
 * SQL OrderBy
 * @package Flat3\Lodata\Drivers\SQL
 */
trait SQLOrderBy
{
    /**
     * Generate SQL order by clauses
     * @return string SQL fragment
     */
    public function generateOrderBy(): string
    {
        $orderby = $this->getOrderBy();

        if (!$orderby->hasValue()) {
            return '';
        }

        $properties = $this->getType()->getDeclaredProperties();

        $ob = implode(', ', array_map(function ($o) use ($properties) {
            [$literal, $direction] = $o;

            if (!$properties->get($literal)) {
                throw new BadRequestException(
                    'invalid_orderby_property',
                    sprintf('The provided property %s was not found in this entity type', $literal)
                );
            }

            return $this->quote($literal)." $direction";
        }, $orderby->getSortOrders()));

        return ' ORDER BY '.$ob;
    }
}
