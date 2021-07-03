<?php

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Interfaces\NameInterface;
use Flat3\Lodata\Interfaces\Operation\ArgumentInterface;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Traits\HasName;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Argument
 * @package Flat3\Lodata\Operation
 */
abstract class Argument implements NameInterface
{
    use HasName;

    /**
     * The reflection parameter on the operations invocation method
     * @var ReflectionParameter $parameter
     */
    protected $parameter;

    public function __construct(ReflectionParameter $parameter)
    {
        $this->parameter = $parameter;
        $this->setName($parameter->getName());
    }

    /**
     * Whether this argument can be null
     * @return bool
     */
    public function isNullable(): bool
    {
        return false;
    }

    /**
     * Generate an instance of the correct type to provide to this argument
     * @param  ReflectionParameter  $parameter  Parameter
     * @return static
     */
    public static function factory(ReflectionParameter $parameter): self
    {
        /** @var ReflectionNamedType $namedType */
        $namedType = $parameter->getType();
        $typeName = $namedType->getName();

        switch (true) {
            case is_a($typeName, EntitySet::class, true):
                return new EntitySetArgument($parameter);

            case is_a($typeName, Transaction::class, true):
                return new TransactionArgument($parameter);

            case is_a($typeName, Entity::class, true):
                return new EntityArgument($parameter);

            case is_a($typeName, Primitive::class, true):
                return new PrimitiveArgument($parameter);

            default:
                return new TypeArgument($parameter);
        }
    }

    /**
     * Generate an instance of this argument with the value of the provided source
     * @param  null  $source
     * @return ArgumentInterface
     */
    abstract public function generate($source = null): ArgumentInterface;
}
