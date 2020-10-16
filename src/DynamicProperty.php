<?php

namespace Flat3\Lodata;

use Closure;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Interfaces\TypeInterface;
use Flat3\Lodata\Type\Property;

class DynamicProperty extends Property
{
    protected $callback;

    public function setCallback(Closure $callback): self
    {
        $this->callback = $callback;
        return $this;
    }

    public function invoke(Entity $entity, Transaction $transaction): ?TypeInterface
    {
        if (!is_callable($this->callback)) {
            return null;
        }

        $result = call_user_func_array($this->callback, [$entity, $transaction]);

        if (!$result instanceof $this->type || $result === null && $this->type instanceof PrimitiveType && !$this->type->isNullable()) {
            throw new InternalServerErrorException('invalid_dynamic_property_type',
                sprintf('The dynamic property %s did not return a value of its defined type', $this->getIdentifier()));
        }

        return $result;
    }
}
