<?php

namespace Flat3\OData;

use Flat3\OData\Exception\Protocol\NotImplementedException;
use Flat3\OData\Operation\Argument;
use RuntimeException;

abstract class Operation extends Resource
{
    /** @var callable $callback */
    protected $callback;

    /** @var ObjectArray $arguments */
    protected $arguments;

    /** @var Type $returnType */
    protected $returnType;

    public function __construct($identifier, $returnType, array $arguments = [])
    {
        parent::__construct($identifier);

        $this->arguments = new ObjectArray();

        foreach ($arguments as $argument) {
            $this->arguments[] = $argument;
        }

        if (is_string($returnType) && is_a($returnType, Type::class, true)) {
            /** @var Type $returnType */
            $returnType = $returnType::factory();
        }

        if (!$returnType instanceof Type) {
            throw new RuntimeException('Invalid return type supplied');
        }

        $this->returnType = $returnType;
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

    public function invoke(array $args): Type
    {
        if (!is_callable($this->callback)) {
            throw new NotImplementedException('no_callback', 'The requested operation has no implementation');
        }

        return call_user_func_array($this->callback, $args);
    }
}
