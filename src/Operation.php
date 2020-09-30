<?php

namespace Flat3\OData;

use Closure;
use Flat3\OData\Exception\Protocol\NotImplementedException;
use Flat3\OData\Interfaces\EdmTypeInterface;
use Flat3\OData\Interfaces\IdentifierInterface;
use Flat3\OData\Interfaces\ResourceInterface;
use Flat3\OData\Operation\Argument;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use RuntimeException;

abstract class Operation implements IdentifierInterface, ResourceInterface
{
    use WithFactory;
    use WithIdentifier;

    const EDM_TYPE = null;

    /** @var callable $callback */
    protected $callback;

    public function __construct($identifier)
    {
        $this->setIdentifier($identifier);
    }

    public function isNullable(): bool
    {
        try {
            $rfn = new ReflectionFunction($this->callback);
            return $rfn->getReturnType()
                ->allowsNull();
        } catch (ReflectionException $e) {
            return false;
        }
    }

    public function getArguments(): ObjectArray
    {
        $rfn = new ReflectionFunction($this->callback);
        $args = new ObjectArray();

        foreach ($rfn->getParameters() as $parameter) {
            $type = $parameter->getType()->getName();
            $arg = new Argument($parameter->getName(), $type::factory(), $parameter->allowsNull());
            $args[] = $arg;
        }

        return $args;
    }

    public function getReturnType(): ?EdmTypeInterface
    {
        $rfn = new ReflectionFunction($this->callback);
        $rt = $rfn->getReturnType();

        if (null === $rt) {
            return null;
        }

        if (!$rt instanceof ReflectionNamedType) {
            throw new RuntimeException('Not named type');
        }

        $name = $rt->getName();
        return new $name();
    }

    public function setCallback(callable $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    public function invoke(array $args)
    {
        if (!$this->callback instanceof Closure) {
            throw new NotImplementedException('no_callback', 'The requested operation has no implementation');
        }

        $j = new ReflectionFunction($this->callback);

        return call_user_func_array($this->callback, $args);
    }
}
