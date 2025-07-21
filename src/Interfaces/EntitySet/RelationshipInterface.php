<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces\EntitySet;

use Flat3\Lodata\Entity;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Property;

/**
 * Update Interface
 * @package Flat3\Lodata\Interfaces\EntitySet
 */
interface RelationshipInterface
{
    /**
     * Add/retain an entity relationship
     * @param  Entity  $source
     * @param  Property  $property  Key
     * @param  Entity  $target  Key
     * @return Entity Entity
     */
    public function link(Entity $source, Property $property, Entity $target): Entity;

    /**
     * Revoke an entity relationship
     * @param  Entity $source
     * @param  Property  $property  Key
     * @param  Entity  $target  Key
     * @return Entity Entity
     */
    public function unlink(Entity $source, Property $property, Entity $target): Entity;
}
