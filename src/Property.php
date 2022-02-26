<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Helper\CollectionType;
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
    const identifier = 'Edm.PropertyPath';

    use HasAnnotations;
    use HasName;

    /**
     * Whether this property is included as part of $search requests
     * @var bool $searchable
     */
    protected $searchable = false;

    /**
     * Whether this property can be used in a $filter expression
     * @var bool $filterable
     */
    protected $filterable = false;

    /**
     * Whether this property can be used as an alternative key
     * @var bool $alternativeKey
     */
    protected $alternativeKey = false;

    /**
     * Whether this property is nullable
     * @var bool $nullable
     */
    protected $nullable = true;

    /**
     * The default value for this property
     * @var callable|mixed $default
     */
    protected $default = null;

    /**
     * The type this property is attached to
     * @var EntityType|PrimitiveType $type
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
     * Whether this property can provide a default value
     * @return bool
     */
    public function hasDefaultValue(): bool
    {
        return $this->default !== null;
    }

    public function hasStaticDefaultValue(): bool
    {
        return $this->default !== null && !is_callable($this->default);
    }

    /**
     * Whether this property has a dynamic default value
     * @return bool
     */
    public function hasDynamicDefaultValue(): bool
    {
        return is_callable($this->default);
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
     * Set the default value for this property
     * @param  callable|mixed  $value
     * @return $this
     */
    public function setDefaultValue($value): self
    {
        $this->default = $value;

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
     * Get the default value of this property
     * @return callable|mixed|null
     */
    public function getDefaultValue()
    {
        return $this->default;
    }

    /**
     * Compute the primitive default value of this property
     * @return Primitive Default value
     */
    public function computeDefaultValue(): Primitive
    {
        $result = is_callable($this->default) ? call_user_func($this->default) : $this->default;

        if ($result instanceof Primitive) {
            return $result;
        }

        return $this->getType()->instance($result);
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
     * Get the primitive type this property is attached to
     * @return PrimitiveType Primitive type
     */
    public function getPrimitiveType(): PrimitiveType
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

    /**
     * Whether this property can be used as an alternative key
     * @return bool
     */
    public function isAlternativeKey(): bool
    {
        return $this->alternativeKey;
    }

    /**
     * Return whether this property can be used in a filter query
     * @return bool
     */
    public function isFilterable(): bool
    {
        return $this->filterable;
    }

    /**
     * Get whether this property is included in search
     * @return bool
     */
    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    /**
     * Assert that the provided value is allowed
     * @param $value
     */
    public function assertAllowsValue($value)
    {
        if (!$this->isNullable() && $value === null) {
            throw new BadRequestException(
                'property_not_nullable',
                sprintf("The property '%s' cannot be set to null", $this->getName())
            );
        }
    }

    /**
     * Get the OpenAPI schema for this property
     * @return array
     */
    public function getOpenAPISchema(): array
    {
        $schema = $this->getType()->getOpenAPISchema();
        $schema['nullable'] = !$this->getType() instanceof CollectionType && $this->nullable;

        if ($this->hasStaticDefaultValue()) {
            $schema['default'] = $this->computeDefaultValue();
        }

        return $schema;
    }
}
