<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Property;

/**
 * SQL OrderBy
 * @package Flat3\Lodata\Drivers\SQL
 */
trait SQLOrderBy
{
    /**
     * Generate expression for orderby parameters
     * @return SQLExpression
     */
    protected function generateOrderBy(): SQLExpression
    {
        $expression = $this->getSQLExpression();

        $orderby = $this->getOrderBy();

        if (!$orderby->hasValue()) {
            return $expression;
        }

        $this->assertValidOrderBy();

        $orders = $orderby->getSortOrders();

        /** @var Property[] $properties */
        $properties = ObjectArray::merge(
            $this->getType()->getProperties(),
            $this->getCompute()->getProperties()
        );

        while ($orders) {
            $order = array_shift($orders);
            [$propertyName, $direction] = $order;

            $property = $properties[$propertyName];

            $expression->pushStatement($this->quoteSingleIdentifier($this->getPropertySourceName($property)));
            $expression->pushStatement($direction);

            if ($this->getDriver() === SQLEntitySet::PostgreSQL) {
                $expression->pushStatement('NULLS LAST');
            }

            if ($orders) {
                $expression->pushComma();
            }
        }

        return $expression;
    }
}