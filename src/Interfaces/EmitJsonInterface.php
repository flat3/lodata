<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Controller\Transaction;

interface EmitJsonInterface extends EmitInterface
{
    /**
     * Emit this item as valid JSON to the client response
     * @param  Transaction  $transaction  Transaction
     */
    public function emitJson(Transaction $transaction): void;
}