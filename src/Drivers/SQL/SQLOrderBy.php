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
     * Navigation properties referenced in $orderby that need LEFT JOINs.
     * Populated by generateOrderBy(), consumed by configureBuilder().
     * @var array
     */
    protected array $orderByNavigationJoins = [];

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
        $this->orderByNavigationJoins = [];

        $orders = $orderby->getSortOrders();

        /** @var Property[] $properties */
        $properties = ObjectArray::merge(
            $this->getType()->getProperties(),
            $this->getCompute()->getProperties()
        );

        while ($orders) {
            $order = array_shift($orders);
            [$propertyName, $direction] = $order;

            if (str_contains($propertyName, '/')) {
                // Navigation property path (e.g., "Status/SortOrder")
                $segments = explode('/', $propertyName);
                [$navPropertyName, $targetPropertyName] = $segments;

                $navigationProperty = $this->getType()->getNavigationProperties()->get($navPropertyName);
                $binding = $this->getBindingByNavigationProperty($navigationProperty);
                $targetSet = $binding->getTarget();
                $targetProperty = $targetSet->getType()->getProperty($targetPropertyName);

                // Store join info for configureBuilder()
                $joinKey = $navPropertyName;
                if (!isset($this->orderByNavigationJoins[$joinKey])) {
                    $this->orderByNavigationJoins[$joinKey] = [
                        'navigationProperty' => $navigationProperty,
                        'binding' => $binding,
                    ];
                }

                // Generate fully qualified column: "target_table"."target_column"
                $targetTable = $targetSet->getTable();
                $targetColumn = $targetSet->getPropertySourceName($targetProperty);
                $expression->pushStatement(
                    $this->quoteSingleIdentifier($targetTable) . '.' .
                    $this->quoteSingleIdentifier($targetColumn)
                );
            } else {
                // Direct property (existing behavior)
                $property = $properties[$propertyName];
                $expression->pushStatement(
                    $this->quoteSingleIdentifier($this->getPropertySourceName($property))
                );
            }

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