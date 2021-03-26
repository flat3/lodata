<?php

namespace Flat3\Lodata\Traits;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;

/**
 * Has Transaction
 * @package Flat3\Lodata\Traits
 */
trait HasTransaction
{
    /**
     * Transaction
     * @var Transaction $transaction
     */
    protected $transaction;

    /**
     * Ensure that this instance has an associated transaction
     * @throws InternalServerErrorException
     */
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

    /**
     * Get the attached transaction
     * @return Transaction Transaction
     */
    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    /**
     * Set the attached transaction
     * @param  Transaction  $transaction  Transaction
     * @return $this
     */
    public function setTransaction(Transaction $transaction)
    {
        $this->transaction = $transaction;
        return $this;
    }

    /**
     * Clone this instance
     * @throws InternalServerErrorException
     */
    public function __clone()
    {
        if ($this->transaction) {
            throw new InternalServerErrorException('cannot_clone', 'Cannot clone instance with configured transaction');
        }
    }
}