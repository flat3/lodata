<?php

declare(strict_types=1);

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\Primitive;
use Flat3\Lodata\Type;

/**
 * Type Argument
 * @package Flat3\Lodata\Operation
 */
class ValueArgument extends Argument
{
    /**
     * Get the type of this primitive
     * @return Type
     */
    public function getType(): Type
    {
        return Type::fromInternalType($this->parameter->getType()->getName());
    }

    public function resolveParameter(?Primitive $parameter)
    {
        return $parameter ? $parameter->get() : null;
    }
}
