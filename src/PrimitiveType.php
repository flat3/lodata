<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Type\Boolean;
use Flat3\Lodata\Type\Double;
use Flat3\Lodata\Type\Int64;
use Flat3\Lodata\Type\String_;
use RuntimeException;
use TypeError;

/**
 * Primitive Type
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530338
 * @package Flat3\Lodata
 */
class PrimitiveType extends Type implements IdentifierInterface
{
    const identifier = 'Edm.PrimitiveType';

    /**
     * The underlying type of this type
     * @var PrimitiveType $underlyingType
     * @internal
     */
    protected $underlyingType;

    /**
     * The factory class name to generate primitives of this type
     * @var string $factory Factory class
     * @internal
     */
    private $factory;

    /**
     * Whether instances of this type can be made null
     * @var bool $nullable
     * @internal
     */
    private $nullable = true;

    public function __construct(string $factory)
    {
        if (!is_a($factory, Primitive::class, true)) {
            throw new RuntimeException('Invalid source for type definition');
        }

        $this->factory = $factory;

        if ($factory::underlyingType) {
            $this->underlyingType = new self($factory::underlyingType);
        }
    }

    /**
     * Set whether instances of this type can be made null
     * @param  bool  $nullable
     * @return $this
     */
    public function setNullable($nullable = true): self
    {
        $this->nullable = $nullable;
        return $this;
    }

    /**
     * Get whether instances of this type can be made null
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * Get the class factory for instances of this type
     * @return string
     */
    public function getFactory(): string
    {
        return $this->factory;
    }

    /**
     * Return whether the provided class name represents instances of this type
     * @param  string  $class
     * @return bool
     */
    public function is(string $class): bool
    {
        return is_a($this->factory, $class, true);
    }

    /**
     * Generate a new primitive instance of this type
     * @param  null  $value
     * @return Primitive Primitive
     */
    public function instance($value = null): Primitive
    {
        return new $this->factory($value, $this->nullable);
    }

    /**
     * Get the fully qualified name of this primitive type
     * @return string Identifier
     */
    public function getIdentifier(): string
    {
        return $this->instance()->getIdentifier();
    }

    /**
     * Get the name of this primitive type
     * @return string Name
     */
    public function getName(): string
    {
        return $this->instance()->getName();
    }

    /**
     * Get the namespace of this primitive typee
     * @return string Namespace
     */
    public function getNamespace(): string
    {
        return $this->instance()->getNamespace();
    }

    /**
     * Get the resolved name of this primitive type based on the provided namespace
     * @param  string  $namespace  Namespace
     * @return string Name
     */
    public function getResolvedName(string $namespace): string
    {
        return $this->instance()->getResolvedName($namespace);
    }

    /**
     * Render this type as an OpenAPI schema
     * @return array
     */
    public function toOpenAPISchema(): array
    {
        return array_merge($this->factory::openApiSchema, [
            'nullable' => $this->nullable,
        ]);
    }

    /**
     * Get the underlying type of this enumerated type
     * @return ?PrimitiveType Underlying type
     */
    public function getUnderlyingType(): ?PrimitiveType
    {
        return $this->underlyingType;
    }

    /**
     * Cast a PHP type to an OData primitive type
     * @param  string  $type  PHP type
     * @return PrimitiveType Primitive type representation
     * @internal
     */
    public static function castInternalType(string $type): PrimitiveType
    {
        switch ($type) {
            case 'string':
                return new PrimitiveType(String_::class);

            case 'float':
                return new PrimitiveType(Double::class);

            case 'int':
                return new PrimitiveType(Int64::class);

            case 'bool':
                return new PrimitiveType(Boolean::class);
        }

        throw new TypeError('Could not cast the provided internal type');
    }

    /**
     * @return string
     * @internal
     */
    public function __toString(): string
    {
        return (string) $this->getIdentifier();
    }
}