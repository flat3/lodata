<?php

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Capabilities\SearchRestrictionsType;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Type\Boolean;

/**
 * Search Restrictions
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class SearchRestrictions extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.SearchRestrictions';

    public function __construct()
    {
        $type = new SearchRestrictionsType();

        $value = new Annotation\Record();
        $value->setType($type);

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(SearchRestrictionsType::Searchable))
            ->setValue(Boolean::factory(true));

        $this->value = $value;
    }

    public function setSearchable(bool $searchable): self
    {
        $this->value[SearchRestrictionsType::Searchable]->setValue(Boolean::factory($searchable));

        return $this;
    }
}