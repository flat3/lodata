<?php

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\Entity;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\Operation\ArgumentInterface;

class EntityArgument extends Argument
{
    public function generate($source = null): ArgumentInterface
    {
        $entityType = Lodata::getEntityTypes()->get($this->getName());

        if (!$entityType) {
            throw new InternalServerErrorException('invalid_entity_type', 'Entity of this type could not be generated');
        }

        $entity = new Entity();
        $entity->setType($entityType);

        return $entity;
    }

    public function getType(): EntityType
    {
        $reflectedType = $this->parameter->getName();
        return Lodata::getEntityType($reflectedType);
    }
}
