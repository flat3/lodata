<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Capabilities\UpdateRestrictionsType;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Type\Boolean;

/**
 * Update Restrictions
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class UpdateRestrictions extends Annotation
{
    public function __construct()
    {
        $this->identifier = new Identifier('Org.OData.Capabilities.V1.UpdateRestrictions');
        $type = new UpdateRestrictionsType();

        $value = new Annotation\Record();
        $value->setType($type);

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(UpdateRestrictionsType::updatable))
            ->setValue(new Boolean(true));

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(UpdateRestrictionsType::deltaUpdateSupported))
            ->setValue(new Boolean(true));

        $this->value = $value;
    }

    public function setUpdatable(bool $updatable): self
    {
        $this->value[UpdateRestrictionsType::updatable]->setValue(new Boolean($updatable));

        return $this;
    }

    public function setDeltaUpdateSupported(bool $deltaUpdateSupported): self
    {
        $this->value[UpdateRestrictionsType::deltaUpdateSupported]->setValue(new Boolean($deltaUpdateSupported));

        return $this;
    }

    public function isUpdatable(): bool
    {
        return $this->value[UpdateRestrictionsType::updatable]->getPrimitiveValue();
    }
}