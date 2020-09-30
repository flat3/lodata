<?php

namespace Flat3\OData;

use Flat3\OData\Interfaces\IdentifierInterface;
use Flat3\OData\Type\EntityType;
use Flat3\OData\Type\PrimitiveType;

abstract class Property implements IdentifierInterface
{
    use HasIdentifier;

    /** @var PrimitiveType|EntityType $type */
    protected $type = null;

    /** @var bool $nullable Whether this property is nullable */
    protected $nullable = true;

    /** @var bool $searchable Whether this property is included as part of $search requests */
    protected $searchable = false;

    /** @var bool $filterable Whether this property can be used in a $filter expression */
    protected $filterable = true;

    /** @var bool $alternativeKey Whether this property can be used as an alternative key */
    protected $alternativeKey = false;

    public function __construct($identifier, $type)
    {
        $this->setIdentifier($identifier);

        if (is_string($type)) {
            /** @var PrimitiveType $type */
            $type = $type::factory();
        }

        $this->type = $type;
    }

    /**
     * Get whether this property is included in search
     *
     * @return bool
     */
    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    /**
     * Set whether this property is included in search
     *
     * @param  bool  $searchable
     *
     * @return $this
     */
    public function setSearchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * @return bool
     */
    public function isFilterable(): bool
    {
        return $this->filterable;
    }

    /**
     * @param  bool  $filterable
     *
     * @return Property
     */
    public function setFilterable(bool $filterable): Property
    {
        $this->filterable = $filterable;

        return $this;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * Set whether this property can be made null
     *
     * @param  bool  $nullable
     *
     * @return $this
     */
    public function setNullable(bool $nullable): self
    {
        $this->nullable = $nullable;

        return $this;
    }

    public function isAlternativeKey(): bool
    {
        return $this->alternativeKey;
    }

    public function setAlternativeKey(bool $alternativeKey = true): self
    {
        $this->alternativeKey = $alternativeKey;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType(PrimitiveType $type)
    {
        $this->type = $type;
    }
}
