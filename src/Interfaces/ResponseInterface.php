<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;

/**
 * Response Interface
 * @package Flat3\Lodata\Interfaces
 */
interface ResponseInterface
{
    /**
     * Generate the client response
     * @param  Transaction  $transaction  Transaction
     * @param  ContextInterface|null  $context  Current context
     * @return Response Client Response
     */
    public function response(Transaction $transaction, ?ContextInterface $context = null): Response;
}