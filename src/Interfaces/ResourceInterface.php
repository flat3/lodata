<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Controller\Transaction;

/**
 * Resource Interface
 * @package Flat3\Lodata\Interfaces
 */
interface ResourceInterface
{
    /**
     * Get the resource URL for this item
     * @param  Transaction  $transaction  Transaction
     * @return string Resource URL
     */
    public function getResourceUrl(Transaction $transaction): string;
}