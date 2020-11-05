<?php

namespace Flat3\Lodata\Traits;

trait UseReferences
{
    protected $useReferences = false;

    public function useReferences(bool $useReferences = true)
    {
        $this->useReferences = $useReferences;

        return $this;
    }

    public function usesReferences(): bool
    {
        return $this->useReferences;
    }
}
