<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Controller\Transaction;

interface EmitStreamInterface extends EmitInterface
{
    /**
     * Emit this item as a stream to the client response
     * @param  Transaction  $transaction  Transaction
     */
    public function emitStream(Transaction $transaction): void;
}