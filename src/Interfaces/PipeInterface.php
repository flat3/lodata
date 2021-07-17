<?php

declare(strict_types=1);

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;

/**
 * Pipe Interface
 * @package Flat3\Lodata\Interfaces
 */
interface PipeInterface
{
    /**
     * Path component handler
     * @param  Transaction  $transaction  Related transaction
     * @param  string  $currentSegment  The current path segment
     * @param  string|null  $nextSegment  The next path segment
     * @param  PipeInterface|null  $argument  The previous path segment
     * @return PipeInterface|null The processed path segment if handled by this implementation
     * @throws PathNotHandledException If the path is not handled by this implementation
     */
    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface;
}