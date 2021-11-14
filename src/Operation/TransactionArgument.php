<?php

declare(strict_types=1);

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\Controller\Transaction;

/**
 * Transaction Argument
 * @package Flat3\Lodata\Operation
 */
class TransactionArgument extends Argument
{
    public function resolveParameter(): Transaction
    {
        return $this->operation->getTransaction();
    }
}
