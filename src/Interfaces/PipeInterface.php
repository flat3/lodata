<?php

namespace Flat3\OData\Interfaces;

use Flat3\OData\Transaction;

interface PipeInterface
{
    public static function pipe(Transaction $transaction, string $pathComponent, ?PipeInterface $argument): ?PipeInterface;
}