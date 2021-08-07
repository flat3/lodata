<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Controller\Transaction;

interface JsonInterface
{
    /**
     * Emit this item as valid JSON to the client response
     * @param  Transaction  $transaction  Transaction
     */
    public function emitJson(Transaction $transaction): void;
}