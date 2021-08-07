<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use Flat3\Lodata\Interfaces\IdentifierInterface;
use RuntimeException;

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
     */
    protected $underlyingType;

    /**
     * Whether instances of this type can be made null
     * @var bool $nullable
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
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->getIdentifier();
    }
}