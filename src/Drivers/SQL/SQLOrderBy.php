<?php

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\Exception\Protocol\BadRequestException;

trait SQLOrderBy
{
    public function generateOrderBy(): string
    {
        $orderby = $this->transaction->getOrderBy();

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

            return "$literal $direction";
        }, $orderby->getSortOrders()));

        return ' ORDER BY '.$ob;
    }
}
