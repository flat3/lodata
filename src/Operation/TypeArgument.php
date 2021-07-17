<?php

declare(strict_types=1);

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\PrimitiveType;
use ReflectionNamedType;

/**
 * Type Argument
 * @package Flat3\Lodata\Operation
 */
class TypeArgument extends PrimitiveArgument
{
    /**
     * Get the type of this primitive
     * @return PrimitiveType
     */
    public function getType(): PrimitiveType
    {
        /** @var ReflectionNamedType $type */
        $type = $this->parameter->getType();

        return PrimitiveType::castInternalType($type->getName());
    }
}
