<?php

namespace Flat3\OData\Resource;

use Countable;
use Flat3\OData\Entity;
use Flat3\OData\Exception\Protocol\NotFoundException;
use Flat3\OData\Exception\Protocol\NotImplementedException;
use Flat3\OData\Interfaces\EntityTypeInterface;
use Flat3\OData\Interfaces\IdentifierInterface;
use Flat3\OData\Interfaces\ResourceInterface;
use Flat3\OData\Internal\ObjectArray;
use Flat3\OData\Primitive;
use Flat3\OData\Property;
use Flat3\OData\Property\Navigation;
use Flat3\OData\Property\Navigation\Binding;
use Flat3\OData\Request\Option;
use Flat3\OData\Traits\HasEntityType;
use Flat3\OData\Traits\HasIdentifier;
use Flat3\OData\Transaction;
use Flat3\OData\Type\EntityType;
use Iterator;
use RuntimeException;

abstract class EntitySet implements EntityTypeInterface, IdentifierInterface, ResourceInterface, Iterator, Countable
{
    use HasIdentifier;
    use HasEntityType;

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

    /** @var null|Entity[] $results Result set from the query */
    protected $results = null;

    /** @var Transaction $transaction */
    protected $transaction;

    /** @var Property $keyProperty */
    protected $keyProperty;

    /** @var Primitive $entityId */
    protected $entityId;

    /** @var bool $isInstance */
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

    public function factory(Transaction $transaction): self
    {
        if ($this->isInstance) {
            throw new RuntimeException('Attempted to clone an instance of an entity set');
        }

        $set = clone $this;

        $set->transaction = $transaction;
        $this->keyProperty = $set->getType()->getKey();

        foreach (
            [
                $transaction->getCount(), $transaction->getFilter(), $transaction->getOrderBy(),
                $transaction->getSearch(), $transaction->getSkip(), $transaction->getTop(),
                $transaction->getExpand()
            ] as $sqo
        ) {
            /** @var Option $sqo */
            if ($sqo->hasValue() && !is_a($this, $sqo::query_interface)) {
                throw new NotImplementedException(
                    'system_query_option_not_implemented',
                    sprintf('The %s system query option is not supported by this entity set', $sqo::param)
                );
            }
        }

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

    public function setKey(Primitive $key): self
    {
        $this->keyProperty = $key->getProperty();
        $this->entityId = $key;
        return $this;
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

        return current($this->results);
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
        return $this->results ? count($this->results) : null;
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
            $this->results = $this->generate();
            $this->topCounter = count($this->results);
        } elseif (!current($this->results) && ($this->topCounter < $this->topLimit)) {
            $this->top = min($this->top, $this->topLimit - $this->topCounter);
            $this->skip += count($this->results);
            $this->results = $this->generate();
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

    public function getEntity(Primitive $key): ?Entity
    {
        $this->setKey($key);
        return $this->current();
    }

    /**
     * Get a single primitive from the entity set
     *
     * @param  Primitive  $key
     * @param  Property  $property
     *
     * @return Primitive
     */
    public function getPrimitive(Primitive $key, Property $property): ?Primitive
    {
        $entity = $this->getEntity($key);

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

    public function entity(): Entity
    {
        return new Entity($this);
    }

    /**
     * Generate a single page of results, using $this->top and $this->skip, loading the results as Entity objects into $this->result_set
     */
    abstract protected function generate(): array;
}