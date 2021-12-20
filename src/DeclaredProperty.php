<?php

declare(strict_types=1);

namespace Flat3\Lodata;

/**
 * Declared Property
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530355
 * @package Flat3\Lodata
 */
class DeclaredProperty extends Property
{
    protected $filterable = true;

    /**
     * Make this property available as an alternative key
     * @param  bool  $alternativeKey
     * @return $this
     */
    public function setAlternativeKey(bool $alternativeKey = true): self
    {
        $this->alternativeKey = $alternativeKey;

        return $this;
    }

    /**
     * Set whether this property is included in search
     * @param  bool  $searchable
     * @return $this
     */
    public function setSearchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Set whether this property can be used in a filter query
     * @param  bool  $filterable
     * @return $this
     */
    public function setFilterable(bool $filterable): Property
    {
        $this->filterable = $filterable;

        return $this;
    }
}
