<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces\EntitySet;

use Flat3\Lodata\Helper\PropertyValue;

/**
 * Delete Interface
 * @package Flat3\Lodata\Interfaces\EntitySet
 */
interface DeleteInterface
{
    /**
     * Delete an entity
     * @param  PropertyValue  $key  Key
     */
    public function delete(PropertyValue $key): void;
}