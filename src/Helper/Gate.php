<?php

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Protocol\ForbiddenException;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\ServiceProvider;
use Illuminate\Support\Facades\Gate as LaravelGate;

class Gate
{
    const READ = 'read';
    const CREATE = 'create';
    const DELETE = 'delete';
    const UPDATE = 'update';
    const QUERY = 'query';
    const EXECUTE = 'execute';

    protected $access;
    protected $resource;
    protected $arguments;
    protected $transaction;

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    public function getResource(): ResourceInterface
    {
        return $this->resource;
    }

    public function getAccess(): string
    {
        return $this->access;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

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

        if (ServiceProvider::usingPreview()) {
            return;
        }

        if (LaravelGate::denies('lodata', $gate)) {
            throw new ForbiddenException('forbidden', 'This request is not permitted');
        }
    }
}