<?php

namespace Flat3\Lodata\Interfaces;

interface ReferenceInterface
{
    public function useReferences(bool $useReferences = true);

    public function usesReferences(): bool;
}