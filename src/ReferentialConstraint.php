<?php

declare(strict_types=1);

namespace Flat3\Lodata;

/**
 * Referential Constraint
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530370
 * @package Flat3\Lodata
 */
class ReferentialConstraint
{
    /**
     * The local property of the constraint
     * @var Property $property Local property
     */
    protected $property;

    /**
     * The referenced property of the constraint
     * @var Property $referencedProperty Referenced property
     */
    protected $referencedProperty;

    public function __construct(Property $property, Property $referenced_property)
    {
        $this->property = $property;
        $this->referencedProperty = $referenced_property;
    }

    /**
     * Get the local property of the constraint
     * @return Property Local property
     */
    public function getProperty(): Property
    {
        return $this->property;
    }

    /**
     * Get the referenced property of the constraint
     * @return Property Referenced property
     */
    public function getReferencedProperty(): Property
    {
        return $this->referencedProperty;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->property->getName().'/'.$this->referencedProperty->getName();
    }
}
