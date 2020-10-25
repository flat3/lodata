<?php

namespace Flat3\Lodata\Traits;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;

trait HasTransaction
{
    /** @var Transaction $transaction */
    protected $transaction;

    public function ensureTransaction(): void
    {
        if ($this->transaction) {
            return;
        }

        throw new InternalServerErrorException(
            'missing_transaction',
            'Attempted to run an operation on an item that has no transaction'
        );
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(Transaction $transaction)
    {
        $this->transaction = $transaction;
        return $this;
    }

    public function __clone()
    {
        if ($this->transaction) {
            throw new InternalServerErrorException('cannot_clone', 'Cannot clone instance with configured transaction');
        }
    }
}