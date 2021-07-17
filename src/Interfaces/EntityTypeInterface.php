<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\EntityType;

/**
 * Entity Type Interface
 * @package Flat3\Lodata\Interfaces
 */
interface EntityTypeInterface extends TypeInterface
{
    /**
     * Get the entity type of this item
     * @return EntityType
     */
    public function getType(): EntityType;

    /**
     * Set the entity type of this item
     * @param  EntityType  $type  Entity type
     * @return mixed
     */
    public function setType(EntityType $type);
}