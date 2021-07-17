<?php

declare(strict_types=1);

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\Entity;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\Operation\ArgumentInterface;

/**
 * Entity Argument
 * @package Flat3\Lodata\Operation
 */
class EntityArgument extends Argument
{
    /**
     * Generate an Entity argument
     * @param  null  $source
     * @return ArgumentInterface
     */
    public function generate($source = null): ArgumentInterface
    {
        $entityType = Lodata::getEntityType($this->getName());

        if (!$entityType) {
            throw new InternalServerErrorException('invalid_entity_type', 'Entity of this type could not be generated');
        }

        $entity = new Entity();
        $entity->setType($entityType);

        return $entity;
    }

    /**
     * Get the entity type
     *
     * @return EntityType
     */
    public function getType(): EntityType
    {
        $reflectedType = $this->parameter->getName();
        return Lodata::getEntityType($reflectedType);
    }
}
