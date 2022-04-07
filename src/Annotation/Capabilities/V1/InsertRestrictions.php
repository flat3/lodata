<?php

declare(strict_types=1);

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Capabilities\InsertRestrictionsType;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Type\Boolean;

/**
 * Insert Restrictions
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class InsertRestrictions extends Annotation
{
    public function __construct()
    {
        $this->identifier = new Identifier('Org.OData.Capabilities.V1.InsertRestrictions');
        $type = new InsertRestrictionsType();

        $value = new Annotation\Record();
        $value->setType($type);

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(InsertRestrictionsType::insertable))
            ->setValue(new Boolean(true));

        $this->value = $value;
    }

    public function setInsertable(bool $searchable): self
    {
        $this->value[InsertRestrictionsType::insertable]->setValue(new Boolean($searchable));

        return $this;
    }

    public function isInsertable(): bool
    {
        return $this->value[InsertRestrictionsType::insertable]->getPrimitiveValue();
    }
}