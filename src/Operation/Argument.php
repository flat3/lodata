<?php

declare(strict_types=1);

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\DynamicProperty;
use Flat3\Lodata\Interfaces\NameInterface;
use Flat3\Lodata\Interfaces\TypeInterface;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Traits\HasName;
use Flat3\Lodata\Type;
use ReflectionParameter;

/**
 * Argument
 * @package Flat3\Lodata\Operation
 */
abstract class Argument implements NameInterface, TypeInterface
{
    use HasName;

    /**
     * The operation attached to this argument
     * @var Operation $operation
     */
    protected $operation;

    /**
     * The reflection parameter on the operations invocation method
     * @var ReflectionParameter $parameter
     */
    protected $parameter;

    /**
     * The OData type of this argument
     * @var Type $type
     */
    protected $type;

    public function __construct(Operation $operation, ReflectionParameter $parameter)
    {
        $this->operation = $operation;
        $this->parameter = $parameter;

        $this->setName($parameter->getName());
    }

    /**
     * Get the type of the argument
     * @return Type
     */
    public function getType(): Type
    {
        return $this->type;
    }

    /**
     * Set the type of the argument
     * @param  Type  $type
     * @return $this
     */
    public function setType(Type $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Whether this argument can be null
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->parameter && $this->parameter->allowsNull();
    }

    /**
     * Get this argument's attached operation
     * @return Operation
     */
    public function getOperation(): Operation
    {
        return $this->operation;
    }

    /**
     * Get the parameter this argument was derived from
     * @return ReflectionParameter
     */
    public function getParameter(): ReflectionParameter
    {
        return $this->parameter;
    }

    /**
     * Get the OpenAPI schema for this argument
     * @return array
     */
    public function getOpenAPISchema(): array
    {
        return (new DynamicProperty('arg', $this->getType()))
            ->setNullable($this->isNullable())
            ->getOpenAPISchema();
    }
}
