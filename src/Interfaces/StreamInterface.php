<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Controller\Transaction;

interface StreamInterface extends ResponseInterface
{
    /**
     * Emit this item as a stream to the client response
     * @param  Transaction  $transaction  Transaction
     */
    public function emitStream(Transaction $transaction): void;
}