<?php

declare(strict_types=1);

namespace Flat3\Lodata\Traits;

/**
 * Use References
 * @package Flat3\Lodata\Traits
 */
trait UseReferences
{
    /**
     * Use references
     * @var bool
     */
    protected $useReferences = false;

    /**
     * Set that this item uses references
     * @param  bool  $useReferences
     * @return $this
     */
    public function useReferences(bool $useReferences = true)
    {
        $this->useReferences = $useReferences;

        return $this;
    }

    /**
     * Get whether this item uses references
     * @return bool
     */
    public function usesReferences(): bool
    {
        return $this->useReferences;
    }
}
