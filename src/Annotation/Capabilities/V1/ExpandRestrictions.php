<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Capabilities\ExpandRestrictionsType;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Type\Boolean;

/**
 * Expand Restrictions
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class ExpandRestrictions extends Annotation
{
    public function __construct()
    {
        $this->identifier = new Identifier('Org.OData.Capabilities.V1.ExpandRestrictions');
        $type = new ExpandRestrictionsType();

        $value = new Annotation\Record();
        $value->setType($type);

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(ExpandRestrictionsType::expandable))
            ->setValue(new Boolean(true));

        $this->value = $value;
    }

    public function setExpandable(bool $expandable): self
    {
        $this->value[ExpandRestrictionsType::expandable]->setValue(new Boolean($expandable));

        return $this;
    }

    public function isExpandable(): bool
    {
        return $this->value[ExpandRestrictionsType::expandable]->getPrimitiveValue();
    }
}