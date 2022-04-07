<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Capabilities\FilterRestrictionsType;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Type\Boolean;

/**
 * Filter Restrictions
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class FilterRestrictions extends Annotation
{
    public function __construct()
    {
        $this->identifier = new Identifier('Org.OData.Capabilities.V1.FilterRestrictions');
        $type = new FilterRestrictionsType();

        $value = new Annotation\Record();
        $value->setType($type);

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(FilterRestrictionsType::filterable))
            ->setValue(new Boolean(true));

        $this->value = $value;
    }

    public function setFilterable(bool $filterable): self
    {
        $this->value[FilterRestrictionsType::filterable]->setValue(new Boolean($filterable));

        return $this;
    }

    public function isFilterable(): bool
    {
        return $this->value[FilterRestrictionsType::filterable]->getPrimitiveValue();
    }
}