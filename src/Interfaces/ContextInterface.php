<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Controller\Transaction;

interface ContextInterface
{
    public function getContextUrl(Transaction $transaction): string;
}