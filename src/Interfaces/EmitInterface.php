<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;

interface EmitInterface
{
    public function emit(Transaction $transaction): void;

    public function response(Transaction $transaction): Response;
}