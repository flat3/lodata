<?php

namespace Flat3\OData\Operation;

use Flat3\OData\Controller\Transaction;
use Flat3\OData\Entity;
use Flat3\OData\EntitySet;
use Flat3\OData\Exception\Protocol\InternalServerErrorException;
use Flat3\OData\Interfaces\ArgumentInterface;
use Flat3\OData\Interfaces\NamedInterface;
use Flat3\OData\PrimitiveType;
use Flat3\OData\Traits\HasName;
use ReflectionParameter;

abstract class Argument implements NamedInterface
{
    use HasName;

    /** @var ReflectionParameter $parameter */
    protected $parameter;

    public function __construct(ReflectionParameter $parameter)
    {
        $this->parameter = $parameter;
        $this->setName($parameter->getName());
    }

    public function isNullable(): bool
    {
        return false;
    }

    public static function factory(ReflectionParameter $parameter): self
    {
        $type = $parameter->getType()->getName();

        switch (true) {
            case is_a($type, EntitySet::class, true):
                return new EntitySetArgument($parameter);

            case is_a($type, Transaction::class, true):
                return new TransactionArgument($parameter);

            case is_a($type, Entity::class, true):
                return new EntityArgument($parameter);

            case is_a($type, PrimitiveType::class, true):
                return new PrimitiveTypeArgument($parameter);
        }

        throw new InternalServerErrorException(
            'invalid_argument_type',
            'Attempted to create an Argument with an unknown type'
        );
    }

    abstract public function generate($source = null): ArgumentInterface;
}
