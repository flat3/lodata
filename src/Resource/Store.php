<?php

namespace Flat3\OData\Resource;

use Flat3\OData\Entity;
use Flat3\OData\Exception\ConfigurationException;
use Flat3\OData\Exception\Protocol\NotFoundException;
use Flat3\OData\Interfaces\IdentifierInterface;
use Flat3\OData\Interfaces\ResourceInterface;
use Flat3\OData\Interfaces\TypeInterface;
use Flat3\OData\Internal\ObjectArray;
use Flat3\OData\Primitive;
use Flat3\OData\Property;
use Flat3\OData\Property\Navigation;
use Flat3\OData\Property\Navigation\Binding;
use Flat3\OData\Traits\HasIdentifier;
use Flat3\OData\Traits\HasType;
use Flat3\OData\Transaction;
use Flat3\OData\Type\EntityType;

abstract class Store implements IdentifierInterface, ResourceInterface, TypeInterface
{
    use HasIdentifier;
    use HasType;

    protected $supportedQueryOptions = [];

    /** @var ObjectArray $sourceMap Mapping of OData properties to source identifiers */
    protected $sourceMap;

    /** @var ObjectArray $navigationBindings Navigation bindings */
    protected $navigationBindings;

    /** @var int $maxPageSize Maximum pagination size allowed for this store */
    protected $maxPageSize = 500;

    public function __construct(string $identifier, EntityType $entityType)
    {
        $this->setIdentifier($identifier);

        $this->type = $entityType;
        $this->navigationBindings = new ObjectArray();
        $this->sourceMap = new ObjectArray();
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

    abstract public function convertResultToEntity($result = null): Entity;

    public function setPropertySourceName(Property $property, string $sourceName): self
    {
        if (!$this->type) {
            throw new ConfigurationException('The mapped type must exist on the bound entity type before a mapping can be added');
        }

        $entityTypeProperty = $this->getTypeProperty($property->getIdentifier());

        if (!$entityTypeProperty) {
            throw new ConfigurationException('The mapped property does not exist on the entity type attached to this entity set');
        }

        $this->sourceMap[$property] = $sourceName;

        return $this;
    }

    public function getTypeProperty(string $property): ?Property
    {
        return $this->getType()->getProperty($property);
    }

    public function hasTypeProperty(string $property): bool
    {
        return $this->getTypeProperty($property) instanceof Property;
    }

    public function getTypeKey(): ?Property
    {
        return $this->getType()->getKey();
    }

    public function getPropertySourceName(Property $property): string
    {
        return $this->sourceMap[$property] ?? $property->getIdentifier()->get();
    }

    public function getSupportedQueryOptions(): array
    {
        return $this->supportedQueryOptions;
    }

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

    abstract public function getEntity(Transaction $transaction, Primitive $key): ?Entity;

    /**
     * Return the number of entities in the result set, taking into account $filter and $search
     *
     * @param  Transaction  $transaction
     *
     * @return int
     */
    public function getCount(Transaction $transaction): int
    {
        $entity_set = $this->getEntitySet($transaction);

        return $entity_set->countResults();
    }

    abstract public function getEntitySet(Transaction $transaction, ?Primitive $key = null): EntitySet;

    public function getKind(): string
    {
        return 'EntitySet';
    }
}
