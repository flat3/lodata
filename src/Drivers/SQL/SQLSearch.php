<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Expression\Node;
use Flat3\Lodata\Expression\Node\Literal;
use Flat3\Lodata\Expression\Node\Operator\Comparison\And_;
use Flat3\Lodata\Expression\Node\Operator\Comparison\Not_;
use Flat3\Lodata\Expression\Node\Operator\Comparison\Or_;
use Flat3\Lodata\Expression\Operator;

/**
 * SQL Search expression parser
 * @package Flat3\Lodata\Drivers\SQL
 */
class SQLSearch extends SQLExpression
{
    /**
     * Generate SQL clauses for the search query option
     * @param  Node  $node  Node
     */
    public function evaluate(Node $node): void
    {
        $left = $node->getLeftNode();
        $right = $node->getRightNode();

        switch (true) {
            case $node instanceof Operator:
                $this->pushStatement('(');

                switch (true) {
                    case $node instanceof Or_:
                        $this->evaluate($left);
                        $this->pushStatement('OR');
                        $this->evaluate($right);
                        break;

                    case $node instanceof And_:
                        $this->evaluate($left);
                        $this->pushStatement('AND');
                        $this->evaluate($right);
                        break;

                    case $node instanceof Not_:
                        $this->pushStatement('NOT');
                        $this->evaluate($left);
                        break;
                }

                $this->pushStatement(')');
                break;

            case $node instanceof Literal:
                $properties = [];

                $type = $this->entitySet->getType();

                /** @var DeclaredProperty $property */
                foreach ($type->getDeclaredProperties() as $property) {
                    if (!$property->isSearchable()) {
                        continue;
                    }

                    $properties[] = $property;
                }

                $this->pushStatement('(');
                while ($properties) {
                    $property = array_shift($properties);
                    $expression = $this->entitySet->propertyToExpression($property);
                    $expression->pushStatement('LIKE ?');
                    $expression->pushParameter('%'.$node->getValue().'%');
                    $this->pushExpression($expression);

                    if ($properties) {
                        $this->pushStatement('OR');
                    }
                }
                $this->pushStatement(')');

                break;
        }
    }
}
