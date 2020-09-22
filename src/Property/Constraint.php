<?php

namespace Flat3\OData\Property;

use Flat3\OData\Property;

class Constraint
{
    /** @var Property $property */
    protected $property;

    /** @var Property $referenced_property */
    protected $referenced_property;

    public function __construct(Property $property, Property $referenced_property)
    {
        $this->property = $property;
        $this->referenced_property = $referenced_property;
    }

    public function getProperty(): Property
    {
        return $this->property;
    }

    public function getReferencedProperty(): Property
    {
        return $this->referenced_property;
    }

    public function __toString()
    {
        return $this->property->getIdentifier()->get().'/'.$this->referenced_property->getIdentifier()->get();
    }
}
