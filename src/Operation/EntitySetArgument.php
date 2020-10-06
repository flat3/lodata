<?php

namespace Flat3\OData\Operation;

use Flat3\OData\Controller\Transaction;
use Flat3\OData\EntitySet;
use Flat3\OData\EntityType;
use Flat3\OData\Exception\Protocol\InternalServerErrorException;
use Flat3\OData\Interfaces\ArgumentInterface;
use Flat3\OData\Model;

class EntitySetArgument extends Argument
{
    public function generate($source = null): ArgumentInterface
    {
        if (!$source instanceof Transaction) {
            throw new InternalServerErrorException(
                'invalid_transaction',
                'The source of an entity set is expected to be a transaction'
            );
        }

        $entitySet = Model::get()->getResources()->get($this->getName());

        if (!$entitySet instanceof EntitySet) {
            throw new InternalServerErrorException(
                'invalid_entity_set',
                'Could not find entity set: '.$this->getName()
            );
        }

        return $entitySet->asInstance($source);
    }

    public function getType(): EntityType
    {
        $reflectedSet = $this->parameter->getName();
        return Model::getResource($reflectedSet)->getType();
    }
}
