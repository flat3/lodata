<?php

declare(strict_types=1);

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Entity;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Type;

/**
 * Entity Argument
 * @package Flat3\Lodata\Operation
 */
class EntityArgument extends Argument
{
    /**
     * Get the entity type
     *
     * @return ComplexType
     */
    public function getType(): Type
    {
        if ($this->type) {
            return $this->type;
        }

        $parameterName = $this->parameter->getName();
        return Lodata::getEntityType($parameterName);
    }

    public function resolveParameter(?Entity $parameter): Entity
    {
        if ($parameter) {
            return $parameter;
        }

        $parameter = new Entity();
        $parameter->setType($this->getType());

        return $parameter;
    }
}
