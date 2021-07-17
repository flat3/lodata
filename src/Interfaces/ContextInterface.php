<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Controller\Transaction;

/**
 * Context Interface
 * @package Flat3\Lodata\Interfaces
 */
interface ContextInterface
{
    /**
     * Get the context URL
     * @param  Transaction  $transaction  Transaction
     * @return string Context URL
     */
    public function getContextUrl(Transaction $transaction): string;
}