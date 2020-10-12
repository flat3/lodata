<?php

namespace Flat3\Lodata\Operation;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Interfaces\ArgumentInterface;

class TransactionArgument extends Argument
{
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
