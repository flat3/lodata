<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Interfaces\IdentifierInterface;
use RuntimeException;

/**
 * Class PrimitiveType
 * @method static self binary()
 * @method static self boolean()
 * @method static self byte()
 * @method static self date()
 * @method static self datetimeoffset()
 * @method static self decimal()
 * @method static self double()
 * @method static self duration()
 * @method static self enum()
 * @method static self guid()
 * @method static self int16()
 * @method static self int32()
 * @method static self int64()
 * @method static self sbyte()
 * @method static self single()
 * @method static self stream()
 * @method static self string()
 * @method static self timeofday()
 * @package Flat3\OData
 */
class PrimitiveType extends Type implements IdentifierInterface
{
    /** @var string $factory */
    private $factory;

    /** @var bool $nullable */
    private $nullable = true;

    public function __construct($class)
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

    public function getFactory(): string
    {
        return $this->factory;
    }

    public function is(string $class): string
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
