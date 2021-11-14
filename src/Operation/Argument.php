<?php

declare(strict_types=1);

namespace Flat3\Lodata\Operation;

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

    public function getType(): Type
    {
        return $this->type;
    }

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

    public function getOperation(): Operation
    {
        return $this->operation;
    }

    public function getParameter(): ReflectionParameter
    {
        return $this->parameter;
    }
}
