<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Expression\Node;
use Flat3\Lodata\Expression\Node\Group;
use Flat3\Lodata\Expression\Node\Literal;
use Flat3\Lodata\Expression\Node\Operator\Comparison\And_;
use Flat3\Lodata\Expression\Node\Operator\Comparison\Not_;
use Flat3\Lodata\Expression\Node\Operator\Comparison\Or_;

/**
 * SQL Search
 * @package Flat3\Lodata\Drivers\SQL
 */
trait SQLSearch
{
    /**
     * Generate SQL clauses for the search query option
     * @param  Node  $node  Node
     * @return bool|null
     */
    public function search(Node $node): ?bool
    {
        switch (true) {
            case $node instanceof Group\Start:
                $this->addWhere('(');

                return true;

            case $node instanceof Group\End:
                $this->addWhere(')');

                return true;

            case $node instanceof Or_:
                $this->addWhere('OR');

                return true;

            case $node instanceof And_:
                $this->addWhere('AND');

                return true;

            case $node instanceof Not_:
                $this->addWhere('NOT');

                return true;

            case $node instanceof Literal:
                $properties = [];

                $type = $this->getType();

                /** @var DeclaredProperty $property */
                foreach ($type->getDeclaredProperties() as $property) {
                    if (!$property->isSearchable()) {
                        continue;
                    }

                    $properties[] = $property;
                }

                $properties = array_map(function ($property) use ($node) {
                    $this->addParameter('%'.$node->getValue().'%');

                    return $this->propertyToField($property).' LIKE ?';
                }, $properties);

                $this->addWhere('( '.implode(' OR ', $properties).' )');

                return true;
        }

        return false;
    }
}
