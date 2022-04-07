<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Capabilities\SortRestrictionsType;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Type\Boolean;

/**
 * Sort Restrictions
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class SortRestrictions extends Annotation
{
    public function __construct()
    {
        $this->identifier = new Identifier('Org.OData.Capabilities.V1.SortRestrictions');
        $type = new SortRestrictionsType();

        $value = new Annotation\Record();
        $value->setType($type);

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(SortRestrictionsType::sortable))
            ->setValue(new Boolean(true));

        $this->value = $value;
    }

    public function setSortable(bool $sortable): self
    {
        $this->value[SortRestrictionsType::sortable]->setValue(new Boolean($sortable));

        return $this;
    }

    public function isSortable(): bool
    {
        return $this->value[SortRestrictionsType::sortable]->getPrimitiveValue();
    }
}