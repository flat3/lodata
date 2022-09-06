<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use Flat3\Lodata\Annotation\Core\V1\Description;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\ConfigurationException;
use Flat3\Lodata\Helper\CollectionType;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Interfaces\AnnotationInterface;
use Flat3\Lodata\Interfaces\NameInterface;
use Flat3\Lodata\Interfaces\TypeInterface;
use Flat3\Lodata\Traits\HasAnnotations;
use Flat3\Lodata\Traits\HasName;
use Flat3\Lodata\Type\Binary;
use Flat3\Lodata\Type\DateTimeOffset;
use Flat3\Lodata\Type\Decimal;
use Flat3\Lodata\Type\Duration;
use Flat3\Lodata\Type\Stream;
use Flat3\Lodata\Type\String_;
use Flat3\Lodata\Type\TimeOfDay;

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

    /**
     * The precision assigned to this property
     * @var null|int $precision
     */
    protected $precision = null;

    /**
     * The maximum length assigned to this property
     * @var null|int $maxLength
     */
    protected $maxLength = null;

    /**
     * The scale assigned to this property
     * @var null|int|string
     */
    protected $scale = null;

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
     * Whether this property has a maximum length
     * @return bool
     */
    public function hasMaxLength(): bool
    {
        return null !== $this->maxLength;
    }

    /**
     * Set the maximum length of this property
     * @param  int  $maxLength
     * @return $this
     */
    public function setMaxLength(int $maxLength): self
    {
        if (!$this->type->instance() instanceof Binary && !$this->type->instance() instanceof Stream && !$this->type->instance() instanceof String_) {
            throw new ConfigurationException(
                'unsupported_max_length',
                sprintf('The property "%s" does not support a max length', $this->getName())
            );
        }

        $this->maxLength = $maxLength;

        return $this;
    }

    /**
     * Get the maximum length of this property
     * @return int|null
     */
    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    /**
     * Whether this property has a defined precision
     * @return bool
     */
    public function hasPrecision(): bool
    {
        return null !== $this->precision;
    }

    /**
     * Set the precision of this property
     * @param  int  $precision
     * @return $this
     */
    public function setPrecision(int $precision): self
    {
        if (!$this->type->instance() instanceof Decimal && !$this->type->instance() instanceof DateTimeOffset && !$this->type->instance() instanceof Duration && !$this->type->instance() instanceof TimeOfDay) {
            throw new ConfigurationException(
                'unsupported_precision',
                sprintf('The property "%s" does not support a precision', $this->getName())
            );
        }

        $this->precision = $precision;

        return $this;
    }

    /**
     * Get the precision of this property
     * @return int|null
     */
    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    /**
     * Whether this property has a defined scale
     * @return bool
     */
    public function hasScale(): bool
    {
        return null !== $this->scale;
    }

    /**
     * Set the scale for this property
     * @param $scale
     * @return $this
     */
    public function setScale($scale): self
    {
        if (!$this->type->instance() instanceof Decimal) {
            throw new ConfigurationException(
                'unsupported_scale',
                sprintf('The property "%s" does not support a scale', $this->getName())
            );
        }

        if (!is_int($scale) && !in_array($scale, [Constants::floating, Constants::variable])) {
            throw new ConfigurationException(
                'unsupported_scale_value',
                sprintf(
                    'The scale for property %s must be a non-negative integer value, or one of the symbolic values "floating" or "variable"',
                    $this->getName()
                )
            );
        }

        $this->scale = $scale;

        return $this;
    }

    /**
     * Get the scale for this property
     * @return int|string|null
     */
    public function getScale()
    {
        return $this->scale;
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

        if (is_string($value) && $this->hasMaxLength() && strlen($value) > $this->getMaxLength()) {
            throw new BadRequestException(
                'property_too_long',
                sprintf("The value property '%s' exceeds the maximum length", $this->getName())
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

        if ($this->hasMaxLength()) {
            $schema['maxLength'] = $this->getMaxLength();
        }

        $scale = $this->getScale();
        if (is_int($scale)) {
            $schema['multipleOf'] = 1 / (10 ** $scale);
        }

        /** @var Description $description */
        $description = $this->getAnnotations()->firstByClass(Description::class);
        if ($description) {
            $schema['description'] = $description->toJson();
        }

        if ($this->hasPrecision()) {
            $precision = $this->getPrecision();

            switch ($scale) {
                case Constants::variable:
                    $schema['maximum'] = (10 ** $precision) - 1;
                    break;

                default:
                    $schema['maximum'] = (10 ** $precision) - (10 ** -$scale);
                    break;
            }

            $schema['minimum'] = -$schema['maximum'];
        }

        return $schema;
    }
}
