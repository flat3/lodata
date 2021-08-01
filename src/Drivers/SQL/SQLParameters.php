<?php

declare(strict_types=1);

namespace Flat3\Lodata\Drivers\SQL;

/**
 * SQL Parameters
 * @package Flat3\Lodata\Drivers\SQL
 */
trait SQLParameters
{
    /**
     * Prepared statement parameters
     * @var string[] $parameters
     */
    protected $parameters = [];

    /**
     * Add a parameter
     * @param  mixed  $parameter
     */
    protected function addParameter($parameter): void
    {
        $this->parameters[] = $parameter;
    }

    /**
     * Get all parameters
     * @return array Parameters
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Clear all parameters
     */
    protected function resetParameters(): void
    {
        $this->parameters = [];
    }
}
