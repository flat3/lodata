<?php

declare(strict_types=1);

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Type;

/**
 * Entity Set Argument
 * @package Flat3\Lodata\Operation
 */
class EntitySetArgument extends Argument
{
    /**
     * Get the entity set type
     * @return EntityType
     */
    public function getType(): Type
    {
        if ($this->type) {
            return $this->type;
        }

        $parameterName = $this->parameter->getName();
        return Lodata::getEntitySet($parameterName)->getType();
    }

    public function resolveParameter(?EntitySet $parameter): EntitySet
    {
        if ($parameter) {
            return $parameter;
        }

        $entitySet = Lodata::getEntitySet($this->getName());
        $parameter = clone $entitySet;
        $parameter->setTransaction($this->operation->getTransaction());

        return $parameter;
    }
}
