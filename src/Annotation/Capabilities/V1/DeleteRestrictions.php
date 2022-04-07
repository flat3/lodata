<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Capabilities\DeleteRestrictionsType;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Type\Boolean;

/**
 * Delete Restrictions
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class DeleteRestrictions extends Annotation
{
    public function __construct()
    {
        $this->identifier = new Identifier('Org.OData.Capabilities.V1.DeleteRestrictions');
        $type = new DeleteRestrictionsType();

        $value = new Annotation\Record();
        $value->setType($type);

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(DeleteRestrictionsType::deletable))
            ->setValue(new Boolean(true));

        $this->value = $value;
    }

    public function setDeletable(bool $deletable): self
    {
        $this->value[DeleteRestrictionsType::deletable]->setValue(new Boolean($deletable));

        return $this;
    }

    public function isDeletable(): bool
    {
        return $this->value[DeleteRestrictionsType::deletable]->getPrimitiveValue();
    }
}