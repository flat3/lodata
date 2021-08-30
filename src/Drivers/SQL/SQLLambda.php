<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers\SQL;

use Flat3\Lodata\Drivers\SQLEntitySet;
use Flat3\Lodata\Exception\Internal\NodeHandledException;
use Flat3\Lodata\Expression\Node;
use Flat3\Lodata\Expression\Node\Operator\Lambda;
use Flat3\Lodata\NavigationBinding;
use Flat3\Lodata\NavigationProperty;
use Flat3\Lodata\ReferentialConstraint;

trait SQLLambda
{
    public function sqlLambdaFilter(Node $node): ?bool
    {
        switch (true) {
            case $node instanceof Lambda:
                list ($lambdaExpression) = $node->getArguments();

                /** @var NavigationProperty $navigationProperty */
                $navigationProperty = $node->getNavigationProperty()->getValue();

                /** @var NavigationBinding $navigationBinding */
                $navigationBinding = $this->getBindingByNavigationProperty($navigationProperty);

                /** @var SQLEntitySet $targetSet */
                $targetSet = $navigationBinding->getTarget();

                /** @var ReferentialConstraint[] $constraints */
                $constraints = $navigationBinding->getPath()->getConstraints()->all();

                $parser = $lambdaExpression->getParser();

                while ($constraints) {
                    $constraint = array_shift($constraints);

                    $this->addWhere(
                        sprintf('( %s = %s ( SELECT %s from %s WHERE',
                            $this->propertyToField($constraint->getProperty()),
                            $node instanceof Lambda\Any ? 'ANY' : 'ALL',
                            $targetSet->propertyToField($constraint->getReferencedProperty()),
                            $targetSet->getTable(),
                        )
                    );

                    $operatingTargetSet = clone $targetSet;

                    $parser->pushEntitySet($operatingTargetSet);
                    $lambdaExpression->compute();
                    $parser->popEntitySet();

                    $this->addWhere($operatingTargetSet->where);
                    $this->parameters = array_merge($this->parameters, $operatingTargetSet->getParameters());

                    $this->addWhere(') )');

                    if ($constraints) {
                        $this->addWhere('OR ');
                    }
                }

                throw new NodeHandledException();
        }

        return false;
    }
}