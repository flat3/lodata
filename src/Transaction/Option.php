<?php

declare(strict_types=1);

namespace Flat3\Lodata\Transaction;

use Flat3\Lodata\Controller\Transaction;

/**
 * System query option
 * @package Flat3\Lodata\Transaction
 */
abstract class Option
{
    public const param = null;
    public const type = 'string';

    /**
     * Option value
     * @var mixed $value
     */
    protected $value = null;

    /**
     * Transaction
     * @var Transaction $transaction
     */
    protected $transaction;

    /**
     * Generate this option from the provided transaction
     * @param  Transaction  $transaction  Transaction
     * @return $this Option
     */
    public function setTransaction(Transaction $transaction): self
    {
        $this->transaction = $transaction;
        $this->setValue($transaction->getSystemQueryOption($this::param));
        return $this;
    }

    /**
     * Get comma separated array values
     * @return array
     */
    public function getCommaSeparatedValues(): array
    {
        return array_filter(array_map('trim', explode(',', (string) $this->value)));
    }

    /**
     * Get the option value
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the option value
     * @param  string|null  $value
     */
    public function setValue(?string $value): void
    {
        $this->value = $value;
    }

    /**
     * Return whether this option is defined
     * @return bool
     */
    public function hasValue(): bool
    {
        return !!$this->value;
    }

    /**
     * Clear the value of this option
     */
    public function clearValue(): void
    {
        $this->value = null;
    }
}
