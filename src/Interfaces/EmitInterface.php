<?php

namespace Flat3\OData\Interfaces;

use Flat3\OData\Transaction;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface EmitInterface
{
    public function emit(Transaction $transaction): void;

    public function response(Transaction $transaction): StreamedResponse;
}