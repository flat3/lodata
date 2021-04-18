<?php

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Capabilities\ExpandRestrictionsType;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Type\Boolean;

/**
 * Expand Restrictions
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class ExpandRestrictions extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.ExpandRestrictions';

    public function __construct()
    {
        $type = new ExpandRestrictionsType();

        $value = new Annotation\Record();
        $value->setType($type);

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(ExpandRestrictionsType::Expandable))
            ->setValue(Boolean::factory(true));

        $this->value = $value;
    }

    public function setExpandable(bool $expandable): self
    {
        $this->value[ExpandRestrictionsType::Expandable]->setValue(Boolean::factory($expandable));

        return $this;
    }
}