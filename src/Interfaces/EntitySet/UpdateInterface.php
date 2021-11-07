<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces\EntitySet;

use Flat3\Lodata\Entity;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Helper\PropertyValues;

/**
 * Update Interface
 * @package Flat3\Lodata\Interfaces\EntitySet
 */
interface UpdateInterface
{
    /**
     * Update an entity
     * @param  PropertyValue  $key  Key
     * @param  PropertyValues  $propertyValues  Property values
     * @return Entity Entity
     */
    public function update(PropertyValue $key, PropertyValues $propertyValues): Entity;
}