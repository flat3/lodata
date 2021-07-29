<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Protocol\ForbiddenException;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Illuminate\Support\Facades\Gate as LaravelGate;

/**
 * Gate
 * @package Flat3\Lodata\Helper
 */
class Gate
{
    const read = 'read';
    const create = 'create';
    const delete = 'delete';
    const update = 'update';
    const query = 'query';
    const execute = 'execute';

    protected $access;
    protected $resource;
    protected $arguments;
    protected $transaction;

    /**
     * Get the transaction attached to this gate
     * @return Transaction
     */
    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    /**
     * Get the resource attached to this gate
     * @return ResourceInterface
     */
    public function getResource(): ResourceInterface
    {
        return $this->resource;
    }

    /**
     * Get the type of access this gate represents
     * @return string
     */
    public function getAccess(): string
    {
        return $this->access;
    }

    /**
     * Get the operation arguments attached to this gate
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Check whether this gate is allowed
     * @param  string  $access  Access type
     * @param  ResourceInterface  $resource  Resource
     * @param  Transaction  $transaction  Transaction
     * @param  array  $arguments  Operation arguments
     */
    public static function check(
        string $access,
        ResourceInterface $resource,
        Transaction $transaction,
        array $arguments = []
    ): void {
        $gate = new self();

        $gate->access = $access;
        $gate->resource = $resource;
        $gate->transaction = $transaction;
        $gate->arguments = $arguments;

        if (!in_array($access, [self::read, self::query]) && config('lodata.readonly') === true) {
            throw new ForbiddenException('forbidden', 'This service is read-only');
        }

        if (config('lodata.authorization') === false) {
            return;
        }

        if (LaravelGate::denies('lodata', $gate)) {
            throw new ForbiddenException('forbidden', 'This request is not permitted');
        }
    }
}