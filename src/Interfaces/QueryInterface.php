<?php

namespace Flat3\Lodata\Interfaces;

interface QueryInterface
{
    /**
     * Generate a single page of results, using $this->top and $this->skip, loading the results as Entity objects into $this->result_set
     */
    public function query(): array;
}