<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces;

/**
 * Name Interface
 * @package Flat3\Lodata\Interfaces
 */
interface NameInterface
{
    /**
     * Get the name of this nominal item
     * @return string Name
     */
    public function getName(): string;
}