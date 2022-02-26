<?php

declare(strict_types=1);

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\Helper\CollectionType;
use Flat3\Lodata\Type;
use Flat3\Lodata\Type\Collection;

/**
 * Collection Argument
 * @package Flat3\Lodata\Operation
 */
class CollectionArgument extends Argument
{
    /**
     * Get the type of this argument
     * @return CollectionType
     */
    public function getType(): Type
    {
        return new CollectionType();
    }

    public function resolveParameter(?Collection $parameter)
    {
        return $this->parameter->getType()->isBuiltin() ? $parameter->toMixed() : $parameter;
    }
}
