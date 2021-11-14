<?php

declare(strict_types=1);

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\Primitive;
use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\Type;
use ReflectionNamedType;

/**
 * Primitive Argument
 * @package Flat3\Lodata\Operation
 */
class PrimitiveArgument extends Argument
{
    /**
     * Get the type of this primitive
     * @return PrimitiveType
     */
    public function getType(): Type
    {
        /** @var ReflectionNamedType $type */
        $type = $this->parameter->getType();

        return new PrimitiveType($type->getName());
    }

    public function resolveParameter(?Primitive $parameter): ?Primitive
    {
        return $parameter;
    }
}
