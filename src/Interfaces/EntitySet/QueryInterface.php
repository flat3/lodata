<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces\EntitySet;

use Flat3\Lodata\Entity;
use Generator;

/**
 * Query Interface
 * @package Flat3\Lodata\Interfaces\EntitySet
 */
interface QueryInterface
{
    /**
     * Generate a single Entity result
     * Must observe the $skip system query option if implementing pagination
     * @return Generator|Entity[]
     */
    public function query(): Generator;
}