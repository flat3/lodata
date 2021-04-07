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
        if (!$this->getSkip()->hasValue()) {
            return '';
        }

        $limits = ' LIMIT ?,?';

        $this->addParameter($this->getSkip()->getValue());
        $this->addParameter($this->getTop()->hasValue() ? $this->getTop()->getValue() : PHP_INT_MAX);

        return $limits;
    }
}
