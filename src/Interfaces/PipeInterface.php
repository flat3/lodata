<?php

namespace Flat3\OData\Interfaces;

use Flat3\OData\Controller\Transaction;

interface PipeInterface
{
    public static function pipe(Transaction $transaction, string $currentComponent, ?string $nextComponent, ?PipeInterface $argument): ?PipeInterface;
}