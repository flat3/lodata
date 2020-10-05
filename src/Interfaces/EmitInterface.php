<?php

namespace Flat3\OData\Interfaces;

use Flat3\OData\Controller\Response;
use Flat3\OData\Controller\Transaction;

interface EmitInterface
{
    public function emit(Transaction $transaction): void;

    public function response(Transaction $transaction): Response;
}