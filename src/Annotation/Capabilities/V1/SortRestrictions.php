<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Capabilities\SortRestrictionsType;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Type\Boolean;

/**
 * Sort Restrictions
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class SortRestrictions extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.SortRestrictions';

    public function __construct()
    {
        $type = new SortRestrictionsType();

        $value = new Annotation\Record();
        $value->setType($type);

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(SortRestrictionsType::sortable))
            ->setValue(Boolean::factory(true));

        $this->value = $value;
    }

    public function setSortable(bool $sortable): self
    {
        $this->value[SortRestrictionsType::sortable]->setValue(Boolean::factory($sortable));

        return $this;
    }
}