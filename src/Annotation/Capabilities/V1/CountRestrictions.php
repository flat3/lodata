<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Capabilities\CountRestrictionsType;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Type\Boolean;

/**
 * Count Restrictions
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class CountRestrictions extends Annotation
{
    public function __construct()
    {
        $this->identifier = new Identifier('Org.OData.Capabilities.V1.CountRestrictions');
        $type = new CountRestrictionsType();

        $value = new Annotation\Record();
        $value->setType($type);

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(CountRestrictionsType::countable))
            ->setValue(new Boolean(true));

        $this->value = $value;
    }

    public function setCountable(bool $countable): self
    {
        $this->value[CountRestrictionsType::countable]->setValue(new Boolean($countable));

        return $this;
    }

    public function isCountable(): bool
    {
        return $this->value[CountRestrictionsType::countable]->getPrimitiveValue();
    }
}