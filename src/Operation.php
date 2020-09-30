<?php

namespace Flat3\OData;

use Flat3\OData\Exception\Protocol\NotImplementedException;
use Flat3\OData\Interfaces\IdentifierInterface;
use Flat3\OData\Interfaces\ResourceInterface;
use Flat3\OData\Operation\Argument;

abstract class Operation implements IdentifierInterface, ResourceInterface
{
    use HasIdentifier;

    const EDM_TYPE = null;

    /** @var callable $callback */
    protected $callback;

    /** @var ObjectArray $arguments */
    protected $arguments;

    /** @var Type $returnType */
    protected $returnType;

    /** @var bool $nullable */
    protected $nullable = true;

    public function __construct($identifier, Type $returnType, array $arguments = [])
    {
        $this->setIdentifier($identifier);

        $this->arguments = new ObjectArray();

        foreach ($arguments as $argument) {
            $this->arguments[] = $argument;
        }

        $this->setReturnType($returnType);
    }

    public function setNullable($nullable): self
    {
        $this->nullable = $nullable;
        return $this;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function setReturnType(Type $returnType): self
    {
        $this->returnType = $returnType;

        return $this;
    }

    public function addArgument(Argument $argument): self
    {
        $this->arguments->add($argument);

        return $this;
    }

    public function getArguments(): ObjectArray
    {
        return $this->arguments;
    }

    public function getReturnType(): Type
    {
        return $this->returnType;
    }

    public function setCallback(callable $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    public function invoke(array $args)
    {
        if (!is_callable($this->callback)) {
            throw new NotImplementedException('no_callback', 'The requested operation has no implementation');
        }

        return call_user_func_array($this->callback, $args);
    }
}
