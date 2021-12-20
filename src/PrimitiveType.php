<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Traits\HasIdentifier;
use RuntimeException;

/**
 * Primitive Type
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530338
 * @package Flat3\Lodata
 */
class PrimitiveType extends Type implements IdentifierInterface
{
    use HasIdentifier;

    const identifier = 'Edm.PrimitiveType';

    /**
     * The underlying type of this type
     * @var PrimitiveType $underlyingType
     */
    protected $underlyingType;

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
        return new $this->factory($value);
    }

    /**
     * Get the fully qualified name of this primitive type
     * @return Identifier Identifier
     */
    public function getIdentifier(): Identifier
    {
        return $this->instance()->getIdentifier();
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
        return $this->getIdentifier()->getQualifiedName();
    }

    /**
     * Get the OpenAPI schema for this primitive type
     * @return array
     */
    public function getOpenAPISchema(): array
    {
        return $this->instance()::openApiSchema;
    }
}