<?php

namespace Flat3\Lodata\Annotation\Capabilities\V1;

use Flat3\Lodata\Annotation;
use Flat3\Lodata\Annotation\Capabilities\InsertRestrictionsType;
use Flat3\Lodata\Annotation\Capabilities\ReadRestrictionsType;
use Flat3\Lodata\Annotation\Capabilities\SearchRestrictionsType;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Type\Boolean;

/**
 * Read Restrictions
 * @package Flat3\Lodata\Annotation\Capabilities\V1
 */
class ReadRestrictions extends Annotation
{
    protected $name = 'Org.OData.Capabilities.V1.ReadRestrictions';

    public function __construct()
    {
        $type = new ReadRestrictionsType();

        $value = new Annotation\Record();
        $value->setType($type);

        $value[] = (new PropertyValue())
            ->setProperty($type->getProperty(ReadRestrictionsType::Readable))
            ->setValue(Boolean::factory(true));

        $this->value = $value;
    }

    public function setReadable(bool $readable): self
    {
        $this->value[ReadRestrictionsType::Readable]->setValue(Boolean::factory($readable));

        return $this;
    }
}