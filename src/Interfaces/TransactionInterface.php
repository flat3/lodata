<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces;

/**
 * Transaction Interface
 * @package Flat3\Lodata\Interfaces
 */
interface TransactionInterface
{
    public function startTransaction();

    public function rollback();

    public function commit();
}
