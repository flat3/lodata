<?php

namespace Flat3\Lodata\Interfaces\EntitySet;

use Flat3\Lodata\Entity;

/**
 * Create Interface
 * @package Flat3\Lodata\Interfaces\EntitySet
 */
interface CreateInterface
{
    /**
     * Create an entity
     * @return Entity
     */
    public function create(): Entity;
}