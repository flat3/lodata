<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Protocol\ConfigurationException;
use Flat3\Lodata\Exception\Protocol\ForbiddenException;
use Flat3\Lodata\Exception\Protocol\UnauthorizedException;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Illuminate\Support\Facades\Gate as LaravelGate;

/**
 * Gate
 * @package Flat3\Lodata\Helper
 * @method static Gate read(ResourceInterface $resource, Transaction $transaction)
 * @method static Gate create(ResourceInterface $resource, Transaction $transaction)
 * @method static Gate delete(ResourceInterface $resource, Transaction $transaction)
 * @method static Gate update(ResourceInterface $resource, Transaction $transaction)
 * @method static Gate query(ResourceInterface $resource, Transaction $transaction)
 * @method static Gate execute(ResourceInterface $resource, Transaction $transaction)
 */
final class Gate
{
    const read = 'read';
    const create = 'create';
    const delete = 'delete';
    const update = 'update';
    const query = 'query';
    const execute = 'execute';

    protected $access;
    protected $resource;
    protected $transaction;

    public function __construct(ResourceInterface $resource, Transaction $transaction)
    {
        $this->resource = $resource;
        $this->transaction = $transaction;
    }

    public static function __callStatic(string $name, array $arguments)
    {
        list ($resource, $transaction) = $arguments;

        return (new self($resource, $transaction))->setAccess(constant(self::class.'::'.$name));
    }

    /**
     * Set the transaction
     * @param  Transaction  $transaction
     * @return $this
     */
    public function setTransaction(Transaction $transaction): self
    {
        $this->transaction = $transaction;

        return $this;
    }

    /**
     * Set the resource
     * @param  ResourceInterface  $resource
     * @return $this
     */
    public function setResource(ResourceInterface $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Set the access type
     * @param  string  $access
     * @return $this
     */
    public function setAccess(string $access): self
    {
        if (!in_array(
            $access,
            [self::read, self::create, self::update, self::delete, self::query, self::execute]
        )) {
            throw new ConfigurationException('invalid_access', 'The access type requested is not valid');
        }

        $this->access = $access;

        return $this;
    }

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
     * Check if this gate is allowed, returning the response
     * @return bool
     */
    public function allows(): bool
    {
        try {
            $this->ensure();
            return true;
        } catch (ForbiddenException $e) {
            return false;
        }
    }

    /**
     * Ensure this gate is allowed, throwing an exception if not
     */
    public function ensure(): void
    {
        if (config('lodata.authorization') === false) {
            if (!in_array($this->access, [self::read, self::query]) && config('lodata.readonly') === true) {
                throw new ForbiddenException('forbidden', 'This service is read-only');
            }

            return;
        }

        if (!LaravelGate::check('lodata', $this)) {
            throw new UnauthorizedException;
        }
    }
}