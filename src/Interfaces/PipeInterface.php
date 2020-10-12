<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Controller\Transaction;

interface PipeInterface
{
    public static function pipe(Transaction $transaction, string $currentComponent, ?string $nextComponent, ?PipeInterface $argument): ?PipeInterface;
}