<?php

namespace Flat3\OData\Interfaces;

use Flat3\OData\Transaction;

interface EmitInterface
{
    public function emit(Transaction $transaction);
}