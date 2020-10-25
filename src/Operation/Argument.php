<?php

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Interfaces\NameInterface;
use Flat3\Lodata\Interfaces\Operation\ArgumentInterface;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Traits\HasName;
use ReflectionParameter;

abstract class Argument implements NameInterface
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

            case is_a($type, Primitive::class, true):
                return new PrimitiveArgument($parameter);
        }

        throw new InternalServerErrorException(
            'invalid_argument_type',
            'Attempted to create an Argument with an unknown type'
        );
    }

    abstract public function generate($source = null): ArgumentInterface;
}
