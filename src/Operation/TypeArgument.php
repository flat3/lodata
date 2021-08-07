<?php

declare(strict_types=1);

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\Type;
use ReflectionNamedType;
use TypeError;

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

        $result = Type::castInternalType($type->getName());

        if (!$result instanceof PrimitiveType) {
            throw new TypeError('invalid_type', 'The provided argument was not a primitive type');
        }

        return $result;
    }
}
