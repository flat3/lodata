<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Controller\Transaction;

interface InstanceInterface
{
    public function asInstance(Transaction $transaction);

    public function isInstance(): bool;

    public function ensureInstance(): void;

    public function getTransaction(): Transaction;
}