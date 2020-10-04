<?php

namespace Flat3\OData\Type;

use Flat3\OData\Interfaces\NamedInterface;
use Flat3\OData\Interfaces\TypeInterface;
use Flat3\OData\Traits\HasName;
use Flat3\OData\Traits\HasType;

abstract class Property implements TypeInterface
{
    use HasName;
    use HasType;

    /** @var bool $nullable Whether this property is nullable */
    protected $nullable = true;

    /** @var bool $searchable Whether this property is included as part of $search requests */
    protected $searchable = false;

    /** @var bool $filterable Whether this property can be used in a $filter expression */
    protected $filterable = true;

    /** @var bool $keyable Whether this property can be used as an alternative key */
    protected $keyable = false;

    public function __construct($name, TypeInterface $type)
    {
        $this->setName($name);
        $this->type = $type;
    }

    public static function factory($name, TypeInterface $type): self
    {
        return new static($name, $type);
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

    public function isKeyable(): bool
    {
        return $this->keyable;
    }

    public function setKeyable(bool $keyable = true): self
    {
        $this->keyable = $keyable;

        return $this;
    }
}
