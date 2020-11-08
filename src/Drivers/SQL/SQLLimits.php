<?php

namespace Flat3\Lodata\Drivers\SQL;

/**
 * SQL Limits
 * @package Flat3\Lodata\Drivers\SQL
 */
trait SQLLimits
{
    use SQLParameters;

    /**
     * Generate SQL limit and offset clauses
     * @return string SQL fragment
     */
    public function generateLimits(): string
    {
        $limits = '';

        if ($this->top === PHP_INT_MAX) {
            return $limits;
        }

        $limits .= ' LIMIT ?';
        $this->addParameter($this->top);

        if (!$this->skip) {
            return $limits;
        }

        $limits .= ' OFFSET ?';
        $this->addParameter($this->skip);

        return $limits;
    }
}
