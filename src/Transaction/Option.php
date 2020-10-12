<?php

namespace Flat3\Lodata\Transaction;

use Flat3\Lodata\Controller\Transaction;

abstract class Option
{
    public const param = null;
    public const type = 'string';
    public const query_interface = null;

    /** @var mixed $value */
    protected $value = null;

    /** @var Transaction $transaction */
    protected $transaction;

    public function transaction(Transaction $transaction): self
    {
        $this->transaction = $transaction;
        $this->setValue($transaction->getSystemQueryOption($this::param));
        return $this;
    }

    /**
     * Get comma separated array values
     *
     * @return array
     */
    public function getCommaSeparatedValues(): array
    {
        return array_filter(array_map('trim', explode(',', $this->value)));
    }

    /**
     * Get the option value
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function setValue(?string $value): void
    {
        $this->value = $value;
    }

    /**
     * Return whether this option is defined
     *
     * @return bool
     */
    public function hasValue(): bool
    {
        return !!$this->value;
    }

    public function clearValue(): void
    {
        $this->value = null;
    }

    public static function factory(Transaction $transaction): self
    {
        $option = new static();
        $option->transaction($transaction);
        return $option;
    }
}
