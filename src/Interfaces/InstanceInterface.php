<?php

namespace Flat3\OData\Interfaces;

use Flat3\OData\Controller\Transaction;

interface InstanceInterface
{
    public function asInstance(Transaction $transaction);

    public function isInstance(): bool;

    public function ensureInstance(): void;

    public function getTransaction(): Transaction;
}