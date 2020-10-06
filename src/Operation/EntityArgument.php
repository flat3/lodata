<?php

namespace Flat3\OData\Operation;

use Flat3\OData\Entity;
use Flat3\OData\Exception\Protocol\InternalServerErrorException;
use Flat3\OData\Interfaces\ArgumentInterface;
use Flat3\OData\Model;

class EntityArgument extends Argument
{
    public function generate($source = null): ArgumentInterface
    {
        $entityType = Model::get()->getEntityTypes()->get($this->getName());

        if (!$entityType) {
            throw new InternalServerErrorException('invalid_entity_type', 'Entity of this type could not be generated');
        }

        $entity = new Entity();
        $entity->setType($entityType);

        return $entity;
    }
}
