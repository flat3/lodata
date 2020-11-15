<?php

namespace Flat3\Lodata\Transaction\Batch;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Transaction\Batch;

class JSON extends Batch
{

    public function emit(Transaction $transaction): void
    {
        // TODO: Implement emit() method.
    }

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        // TODO: Implement response() method.
    }
}