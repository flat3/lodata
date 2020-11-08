<?php

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Interfaces\Operation\ArgumentInterface;

/**
 * Transaction Argument
 * @package Flat3\Lodata\Operation
 */
class TransactionArgument extends Argument
{
    /**
     * Provide the current transaction as an invocation argument
     * @param  null  $source
     * @return ArgumentInterface
     */
    public function generate($source = null): ArgumentInterface
    {
        if (!$source instanceof Transaction) {
            throw new InternalServerErrorException('invalid_transaction',
                'The source of the transaction type argument was not a Transaction'
            );
        }

        return $source;
    }
}
