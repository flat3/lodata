<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Interfaces\IdentifierInterface;
use RuntimeException;

/**
 * Class PrimitiveType
 * @package Flat3\Lodata
 */
class PrimitiveType extends Type implements IdentifierInterface
{
    /** @var string $factory */
    private $factory;

    /** @var bool $nullable */
    private $nullable = true;

    public function __construct(string $class)
    {
        if (!is_a($class, Primitive::class, true)) {
            throw new RuntimeException('Invalid source for type definition');
        }

        $this->factory = $class;
    }

    public function setNullable($nullable = true): self
    {
        $this->nullable = $nullable;
        return $this;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function getFactory(): string
    {
        return $this->factory;
    }

    public function is(string $class): bool
    {
        return is_a($this->factory, $class, true);
    }

    public function instance($value = null): Primitive
    {
        return new $this->factory($value, $this->nullable);
    }

    public function getIdentifier(): string
    {
        return $this->instance()->getIdentifier();
    }

    public function getName(): string
    {
        return $this->instance()->getName();
    }

    public function getNamespace(): string
    {
        return $this->instance()->getNamespace();
    }

    public function getResolvedName(string $namespace): string
    {
        return $this->instance()->getResolvedName($namespace);
    }
}
