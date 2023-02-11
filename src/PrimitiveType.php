<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Traits\HasIdentifier;

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
     * The underlying type of this primitive type
     * @var Type $underlyingType
     */
    protected $underlyingType;

    public function __construct(string $factory)
    {
        $this->factory = $factory;

        if ($factory::underlyingType) {
            $this->setUnderlyingType(new self($factory::underlyingType));
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
     * Get the underlying type of this type
     * @return ?Type Type
     */
    public function getUnderlyingType(): ?Type
    {
        return $this->underlyingType;
    }

    /**
     * Set the underlying type of this resource
     * @param  Type  $underlyingType  Underlying type
     * @return $this
     */
    public function setUnderlyingType(Type $underlyingType): self
    {
        $this->underlyingType = $underlyingType;

        return $this;
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
        return $this->instance()->getOpenAPISchema();
    }
}