<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces;

/**
 * Reference Interface
 * @package Flat3\Lodata\Interfaces
 */
interface ReferenceInterface
{
    /**
     * Set that this item uses references
     * @param  bool  $useReferences
     * @return mixed
     */
    public function useReferences(bool $useReferences = true);

    /**
     * Whether this item uses references
     * @return bool
     */
    public function usesReferences(): bool;
}