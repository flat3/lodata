<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces\EntitySet;

use Flat3\Lodata\Entity;
use Flat3\Lodata\Helper\PropertyValue;

/**
 * Read Interface
 * @package Flat3\Lodata\Interfaces\EntitySet
 */
interface ReadInterface
{
    /**
     * Read a entity
     * @param  PropertyValue  $key  Key
     * @return Entity Entity
     */
    public function read(PropertyValue $key): Entity;
}