<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces\EntitySet;

use Flat3\Lodata\Entity;
use Flat3\Lodata\Helper\PropertyValues;

/**
 * Create Interface
 * @package Flat3\Lodata\Interfaces\EntitySet
 */
interface CreateInterface
{
    /**
     * Create an entity
     * @param  PropertyValues  $propertyValues  Property values
     * @return Entity
     */
    public function create(PropertyValues $propertyValues): Entity;
}