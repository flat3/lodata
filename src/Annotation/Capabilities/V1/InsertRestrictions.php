<?php

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Capabilities\InsertRestrictionsType;
use Flat3\Lodata\Annotation\Capabilities\SearchRestrictionsType;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Type\Boolean;

/**
 * Insert Restrictions
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class InsertRestrictions extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.InsertRestrictions';

    public function __construct()
    {
        $type = new InsertRestrictionsType();

        $value = new Annotation\Record();
        $value->setType($type);

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(InsertRestrictionsType::Insertable))
            ->setValue(Boolean::factory(true));

        $this->value = $value;
    }

    public function setInsertable(bool $searchable): self
    {
        $this->value[InsertRestrictionsType::Insertable]->setValue(Boolean::factory($searchable));

        return $this;
    }
}