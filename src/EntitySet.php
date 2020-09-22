<?php

namespace Flat3\OData;

use Flat3\OData\Expression\Event;

abstract class EntitySet
{
    /** @var Transaction $transaction */
    protected $transaction;

    /** @var Store $store */
    protected $store;

    /** @var Property $entityKey */
    protected $entityKey;

    /** @var Primitive $entityId */
    protected $entityId;

    /** @var null|array $resultSet Result set from the query */
    protected $resultSet = null;

    /** @var int $top Page size to return from the query */
    protected $top = PHP_INT_MAX;

    /** @var int $skip Skip value to use in the query */
    protected $skip = 0;

    /** @var int $topCounter Total number of records fetched for internal pagination */
    private $topCounter = 0;

    /** @var int Limit of number of records to evaluate from the source */
    private $topLimit = PHP_INT_MAX;

    public function __construct(Store $store, Transaction $transaction, ?Primitive $key = null)
    {
        $this->store = $store;
        $this->transaction = $transaction;
        $this->entityKey = $key ? $key->getProperty() : $store->getEntityType()->getKey();
        $this->entityId = $key;

        $maxPageSize = $store->getMaxPageSize();
        $skip = $transaction->getSkip();
        $top = $transaction->getTop();
        $this->top = $top->hasValue() && ($top->getValue() < $maxPageSize) ? $top->getValue() : $maxPageSize;

        if ($skip->hasValue()) {
            $this->skip = $skip->getValue();
        }

        if ($top->hasValue()) {
            $this->topLimit = $top->getValue();
        }
    }

    public function getStore(): Store
    {
        return $this->store;
    }

    /**
     * Handle a discovered expression symbol in the filter query
     *
     * @param  Event  $event
     *
     * @return bool True if the event was handled
     */
     abstract public function filter(Event $event): ?bool;

    /**
     * Handle a discovered expression symbol in the search query
     *
     * @param  Event  $event
     *
     * @return bool True if the event was handled
     */
    abstract public function search(Event $event): ?bool;

    /**
     * The number of items in this entity set query, including filters, without limit clauses
     *
     * @return int
     */
    abstract public function countResults(): int;

    /**
     * @return ObjectArray
     */
    public function getDeclaredProperties(): ObjectArray
    {
        return $this->store->getEntityType()->getDeclaredProperties();
    }

    public function writeToResponse(Transaction $transaction): void
    {
        while ($this->hasResult()) {
            $entity = $this->getCurrentResultAsEntity();

            $transaction->outputJsonObjectStart();
            $entity->writeToResponse($transaction);
            $transaction->outputJsonObjectEnd();

            $this->nextResult();

            if (!$this->hasResult()) {
                break;
            }

            $transaction->outputJsonSeparator();
        }
    }

    /**
     * Whether there is a current entity in the result set
     * Implements internal pagination
     *
     * @return bool
     */
    public function hasResult(): bool
    {
        if (0 === $this->top) {
            return false;
        }

        if ($this->resultSet === null) {
            $this->generateResultSet();
            $this->topCounter = count($this->resultSet);
        } elseif (!current($this->resultSet) && ($this->topCounter < $this->topLimit)) {
            $this->top = min($this->top, $this->topLimit - $this->topCounter);
            $this->skip += count($this->resultSet);
            $this->resultSet = null;
            $this->generateResultSet();
            $this->topCounter += count($this->resultSet);
        }

        return !!current($this->resultSet);
    }

    /**
     * Perform the query, observing $this->top and $this->skip, loading the results into $this->result_set
     */
    abstract protected function generateResultSet(): void;

    /**
     * The current entity
     *
     * @return Entity
     */
    public function getCurrentResultAsEntity(): ?Entity
    {
        if (null === $this->resultSet && !$this->hasResult()) {
            return null;
        }

        return $this->store->convertResultToEntity(current($this->resultSet));
    }

    /**
     * Move to the next result
     */
    public function nextResult(): void
    {
        next($this->resultSet);
    }
}
