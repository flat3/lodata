<?php

namespace Flat3\OData;

use Closure;
use Flat3\OData\Exception\Protocol\NotImplementedException;
use Flat3\OData\Interfaces\IdentifierInterface;
use Flat3\OData\Interfaces\ResourceInterface;
use Flat3\OData\Interfaces\TypeInterface;
use Flat3\OData\Operation\Argument;
use Flat3\OData\Type\PrimitiveType;
use ReflectionException;
use ReflectionFunction;
use RuntimeException;

abstract class Operation implements IdentifierInterface, ResourceInterface, TypeInterface
{
    use WithFactory;
    use HasIdentifier;
    use HasType;

    /** @var callable $callback */
    protected $callback;

    public function __construct($identifier)
    {
        $this->setIdentifier($identifier);
    }

    public function returnsCollection(): bool
    {
        $rfc = new ReflectionFunction($this->callback);
        $rt = $rfc->getReturnType();
        $tn = $rt->getName();
        switch (true) {
            case is_a($tn, EntitySet::class, true);
                return true;

            case is_a($tn, Entity::class, true);
            case is_a($tn, PrimitiveType::class, true);
                return false;
        }

        throw new RuntimeException('Invalid return type');
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

        return call_user_func_array($this->callback, $args);
    }
}
