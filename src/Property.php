<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Interfaces\AnnotationInterface;
use Flat3\Lodata\Interfaces\NameInterface;
use Flat3\Lodata\Interfaces\TypeInterface;
use Flat3\Lodata\Traits\HasAnnotations;
use Flat3\Lodata\Traits\HasName;

/**
 * Property
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_StructuralProperty
 * @package Flat3\Lodata
 */
abstract class Property implements NameInterface, TypeInterface, AnnotationInterface
{
    use HasAnnotations;
    use HasName;

    /**
     * Whether this property is nullable
     * @var bool $nullable
     * @internal
     */
    protected $nullable = true;

    /**
     * The type this property is attached to
     * @var EntityType|PrimitiveType $type
     * @internal
     */
    protected $type;

    public function __construct($name, Type $type)
    {
        $this->setName($name);
        $this->type = $type;
    }

    /**
     * Whether instances of this property can be made null
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * Set whether instances of this property can be made null
     * @param  bool  $nullable
     * @return $this
     */
    public function setNullable(bool $nullable): self
    {
        $this->nullable = $nullable;

        return $this;
    }

    /**
     * Get the entity type this property is attached to
     * @return EntityType Entity type
     */
    public function getEntityType(): EntityType
    {
        return $this->type;
    }

    /**
     * Get the primitive type this property is attached to
     * @return PrimitiveType Primitive type
     */
    public function getPrimitiveType(): PrimitiveType
    {
        return $this->type;
    }

    /**
     * Get the type this property is attached to
     * @return Type Type
     */
    public function getType(): Type
    {
        return $this->type;
    }

    /**
     * Set the type this property is attached to
     * @param  Type  $type  Type
     * @return $this
     */
    public function setType(Type $type): self
    {
        $this->type = $type;

        return $this;
    }
}
