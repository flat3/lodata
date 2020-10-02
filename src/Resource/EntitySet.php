<?php

namespace Flat3\OData\Resource;

use Flat3\OData\Entity;
use Flat3\OData\Exception\Protocol\NotFoundException;
use Flat3\OData\Interfaces\CountInterface;
use Flat3\OData\Interfaces\EntityTypeInterface;
use Flat3\OData\Interfaces\IdentifierInterface;
use Flat3\OData\Interfaces\ResourceInterface;
use Flat3\OData\Internal\ObjectArray;
use Flat3\OData\Primitive;
use Flat3\OData\Property;
use Flat3\OData\Property\Navigation;
use Flat3\OData\Property\Navigation\Binding;
use Flat3\OData\Traits\HasEntityType;
use Flat3\OData\Traits\HasIdentifier;
use Flat3\OData\Transaction;
use Flat3\OData\Type\EntityType;
use Iterator;
use RuntimeException;

abstract class EntitySet implements EntityTypeInterface, IdentifierInterface, ResourceInterface, Iterator, CountInterface
{
    use HasIdentifier;
    use HasEntityType;

    protected $supportedQueryOptions = [];

    /** @var ObjectArray $navigationBindings Navigation bindings */
    protected $navigationBindings;

    /** @var int $top Page size to return from the query */
    protected $top = PHP_INT_MAX;

    /** @var int $skip Skip value to use in the query */
    protected $skip = 0;

    /** @var int $topCounter Total number of records fetched for internal pagination */
    private $topCounter = 0;

    /** @var int Limit of number of records to evaluate from the source */
    protected $topLimit = PHP_INT_MAX;

    /** @var int $maxPageSize Maximum pagination size allowed for this entity set */
    protected $maxPageSize = 500;

    /** @var null|array $results Result set from the query */
    protected $results = null;

    /** @var Transaction $transaction */
    protected $transaction;

    /** @var Property $entityKey */
    protected $entityKey;

    /** @var Primitive $entityId */
    protected $entityId;

    protected $isInstance = false;

    public function __construct(string $identifier, EntityType $entityType)
    {
        $this->setIdentifier($identifier);

        $this->type = $entityType;
        $this->navigationBindings = new ObjectArray();
    }

    public function __clone()
    {
        $this->isInstance = true;
    }

    public function factory(Transaction $transaction = null, ?Primitive $key = null): self
    {
        if ($this->isInstance) {
            throw new RuntimeException('Attempted to clone an instance of an entity set');
        }

        $set = clone $this;

        $set->transaction = $transaction;
        $set->entityKey = $key ? $key->getProperty() : $set->getType()->getKey();
        $set->entityId = $key;

        $maxPageSize = $set->getMaxPageSize();
        $skip = $transaction->getSkip();
        $top = $transaction->getTop();
        $set->top = $top->hasValue() && ($top->getValue() < $maxPageSize) ? $top->getValue() : $maxPageSize;

        if ($skip->hasValue()) {
            $set->skip = $skip->getValue();
        }

        if ($top->hasValue()) {
            $set->topLimit = $top->getValue();
        }

        return $set;
    }

    public function getKind(): string
    {
        return 'EntitySet';
    }

    /**
     * The current entity
     *
     * @return Entity
     */
    public function current(): ?Entity
    {
        if (null === $this->results && !$this->valid()) {
            return null;
        }

        return $this->toEntity(current($this->results));
    }

    /**
     * Move to the next result
     */
    public function next(): void
    {
        next($this->results);
    }

    public function key()
    {
        $entity = $this->current();

        if (!$entity) {
            return null;
        }

        return $entity->getEntityId();
    }

    public function rewind()
    {
        throw new RuntimeException('Entity sets cannot be rewound');
    }

    public function count()
    {
        return null;
    }

    /**
     * Whether there is a current entity in the result set
     * Implements internal pagination
     *
     * @return bool
     */
    public function valid(): bool
    {
        if (0 === $this->top) {
            return false;
        }

        if ($this->results === null) {
            $this->generate();
            $this->topCounter = count($this->results);
        } elseif (!current($this->results) && ($this->topCounter < $this->topLimit)) {
            $this->top = min($this->top, $this->topLimit - $this->topCounter);
            $this->skip += count($this->results);
            $this->results = null;
            $this->generate();
            $this->topCounter += count($this->results);
        }

        return !!current($this->results);
    }

    public function getMaxPageSize(): int
    {
        return $this->maxPageSize;
    }

    public function setMaxPageSize(int $maxPageSize): self
    {
        $this->maxPageSize = $maxPageSize;

        return $this;
    }

    public function getSupportedQueryOptions(): array
    {
        return $this->supportedQueryOptions;
    }

    abstract public function getEntity(Transaction $transaction, Primitive $key): ?Entity;

    /**
     * Get a single primitive from the entity set
     *
     * @param  Transaction  $transaction
     * @param  Primitive  $key
     * @param  Property  $property
     *
     * @return Primitive
     */
    public function getPrimitive(Transaction $transaction, Primitive $key, Property $property): ?Primitive
    {
        $entity = $this->getEntity($transaction, $key);

        if (null === $entity) {
            throw NotFoundException::factory()
                ->target($key->toJson());
        }

        return $entity->getPrimitive($property);
    }


    public function addNavigationBinding(Binding $binding): self
    {
        $this->navigationBindings[] = $binding;

        return $this;
    }

    public function getNavigationBindings(): ObjectArray
    {
        return $this->navigationBindings;
    }

    public function getBindingByNavigationProperty(Navigation $property): ?Binding
    {
        /** @var Binding $navigationBinding */
        foreach ($this->navigationBindings as $navigationBinding) {
            if ($navigationBinding->getPath() === $property) {
                return $navigationBinding;
            }
        }

        return null;
    }

    /**
     * @return ObjectArray
     */
    public function getDeclaredProperties(): ObjectArray
    {
        return $this->getType()->getDeclaredProperties();
    }

    public function getTypeProperty(string $property): ?Property
    {
        return $this->getType()->getProperty($property);
    }

    public function getTypeKey(): ?Property
    {
        return $this->getType()->getKey();
    }

    public function writeToResponse(Transaction $transaction): void
    {
        while ($this->valid()) {
            $entity = $this->current();

            $transaction->outputJsonObjectStart();
            $entity->writeToResponse($transaction);
            $transaction->outputJsonObjectEnd();

            $this->next();

            if (!$this->valid()) {
                break;
            }

            $transaction->outputJsonSeparator();
        }
    }

    /**
     * Perform the query, observing $this->top and $this->skip, loading the results into $this->result_set
     */
    abstract protected function generate(): void;

    abstract protected function toEntity($data): Entity;
}