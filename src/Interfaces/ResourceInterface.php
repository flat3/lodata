<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Controller\Transaction;

interface ResourceInterface
{
    public function getResourceUrl(Transaction $transaction): string;
}