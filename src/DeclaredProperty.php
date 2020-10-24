<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Helper\PropertyValue;

class DeclaredProperty extends Property
{
    /** @var bool $searchable Whether this property is included as part of $search requests */
    protected $searchable = false;

    /** @var bool $filterable Whether this property can be used in a $filter expression */
    protected $filterable = true;

    /** @var bool $alternativeKey Whether this property can be used as an alternative key */
    protected $alternativeKey = false;

    public function isAlternativeKey(): bool
    {
        return $this->alternativeKey;
    }

    public function setAlternativeKey(bool $alternativeKey = true): self
    {
        $this->alternativeKey = $alternativeKey;

        return $this;
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
}
