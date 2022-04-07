<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Capabilities\SearchRestrictionsType;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Type\Boolean;

/**
 * Search Restrictions
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class SearchRestrictions extends Annotation
{
    public function __construct()
    {
        $this->identifier = new Identifier('Org.OData.Capabilities.V1.SearchRestrictions');
        $type = new SearchRestrictionsType();

        $value = new Annotation\Record();
        $value->setType($type);

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(SearchRestrictionsType::searchable))
            ->setValue(new Boolean(true));

        $this->value = $value;
    }

    public function setSearchable(bool $searchable): self
    {
        $this->value[SearchRestrictionsType::searchable]->setValue(new Boolean($searchable));

        return $this;
    }

    public function isSearchable(): bool
    {
        return $this->value[SearchRestrictionsType::searchable]->getPrimitiveValue();
    }
}