<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\DeclaredProperty;

/**
 * Property values
 * @package Flat3\Lodata\Helper
 */
class PropertyValues extends ObjectArray
{
    protected $types = [PropertyValue::class];

    /**
     * @return PropertyValue[]|$this
     */
    public function getDeclaredPropertyValues(): self
    {
        return $this->filter(function (PropertyValue $propertyValue) {
            return $propertyValue->getProperty() instanceof DeclaredProperty;
        });
    }
}