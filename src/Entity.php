<?php

namespace Flat3\Lodata;

use ArrayAccess;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Exception\Protocol\MethodNotAllowedException;
use Flat3\Lodata\Exception\Protocol\NoContentException;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Helper\ETag;
use Flat3\Lodata\Helper\Gate;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Helper\Url;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\EmitInterface;
use Flat3\Lodata\Interfaces\EntitySet\DeleteInterface;
use Flat3\Lodata\Interfaces\EntitySet\UpdateInterface;
use Flat3\Lodata\Interfaces\EntityTypeInterface;
use Flat3\Lodata\Interfaces\Operation\ArgumentInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ReferenceInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Traits\HasTransaction;
use Flat3\Lodata\Traits\UseReferences;
use Flat3\Lodata\Transaction\MetadataContainer;
use Flat3\Lodata\Transaction\NavigationRequest;
use Illuminate\Http\Request;

/**
 * Entity
 * @link https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#_Toc31358838
 * @package Flat3\Lodata
 */
class Entity implements ResourceInterface, ReferenceInterface, EntityTypeInterface, ContextInterface, ArrayAccess, EmitInterface, PipeInterface, ArgumentInterface
{
    use UseReferences;
    use HasTransaction;

    /**
     * Property values on this entity instance
     * @var ObjectArray $propertyValues
     * @internal
     */
    private $propertyValues;

    /**
     * The entity set this entity belongs to
     * @var EntitySet $entitySet
     * @internal
     */
    private $entitySet;

    /**
     * The entity type of this entity
     * @var EntityType $type
     * @internal
     */
    private $type;

    /**
     * The metadata about this entity
     * @var MetadataContainer $metadata
     * @internal
     */
    protected $metadata = null;

    public function __construct()
    {
        $this->propertyValues = new ObjectArray();
    }

    /**
     * Set the entity set that contains this entity
     * @param  EntitySet  $entitySet  Entity set
     * @return $this
     */
    public function setEntitySet(EntitySet $entitySet): self
    {
        $this->entitySet = $entitySet;
        $this->type = $entitySet->getType();
        return $this;
    }

    public function emit(Transaction $transaction): void
    {
        $transaction = $this->transaction ?: $transaction;
        $entityType = $this->getType();
        $navigationRequests = $transaction->getNavigationRequests();

        /** @var GeneratedProperty $generatedProperty */
        foreach ($this->getType()->getGeneratedProperties() as $generatedProperty) {
            $generatedProperty->generatePropertyValue($this);
        }

        /** @var NavigationProperty $navigationProperty */
        foreach ($this->getType()->getNavigationProperties() as $navigationProperty) {
            /** @var NavigationRequest $navigationRequest */
            $navigationRequest = $navigationRequests->get($navigationProperty->getName());

            if (!$navigationRequest) {
                continue;
            }

            $navigationPath = $navigationRequest->path();

            /** @var NavigationProperty $navigationProperty */
            $navigationProperty = $entityType->getNavigationProperties()->get($navigationPath);
            $navigationRequest->setNavigationProperty($navigationProperty);

            if (null === $navigationProperty) {
                throw new BadRequestException(
                    'nonexistent_expand_path',
                    sprintf(
                        'The requested expand path "%s" does not exist on this entity type',
                        $navigationPath
                    )
                );
            }

            if (!$navigationProperty->isExpandable()) {
                throw new BadRequestException(
                    'path_not_expandable',
                    sprintf(
                        'The requested path "%s" is not available for expansion on this entity type',
                        $navigationPath
                    )
                );
            }

            $navigationProperty->generatePropertyValue($transaction, $navigationRequest, $this);
        }

        $transaction->outputJsonObjectStart();

        $metadata = $this->metadata ?: $transaction->getMetadata()->getContainer();
        $metadata['type'] = '#'.$this->getType()->getIdentifier();

        if ($this->entitySet && $this->getEntityId()) {
            $metadata['id'] = $this->getResourceUrl($transaction);

            if ($this->usesReferences()) {
                $metadata['id'] = sprintf(
                    "%s(%s)",
                    $this->entitySet->getName(),
                    $this->getEntityId()->getValue()->get()
                );

                $metadata->addRequiredProperty('id');
            }

            $metadata['readLink'] = $metadata['id'];
        }

        $requiresSeparator = false;

        if ($metadata->hasMetadata()) {
            $transaction->outputJsonKV($metadata->getMetadata());
            $requiresSeparator = true;
        }

        $this->propertyValues->rewind();

        while (true) {
            if ($this->usesReferences()) {
                break;
            }

            if (!$this->propertyValues->valid()) {
                break;
            }

            /** @var PropertyValue $propertyValue */
            $propertyValue = $this->propertyValues->current();

            if (!$propertyValue->shouldEmit($transaction)) {
                $this->propertyValues->next();
                continue;
            }

            if ($requiresSeparator) {
                $transaction->outputJsonSeparator();
            }

            if ($propertyValue->getProperty() instanceof NavigationProperty) {
                $propertyMetadata = $this->getExpansionMetadata($transaction, $propertyValue);

                if ($propertyMetadata->hasMetadata()) {
                    $transaction->outputJsonKV($propertyMetadata->getMetadata());
                    $transaction->outputJsonSeparator();
                }
            }

            $transaction->outputJsonKey($propertyValue->getProperty()->getName());

            $value = $propertyValue->getValue();
            if ($value instanceof EmitInterface) {
                $propertyValue->getValue()->emit($transaction);
            } else {
                $transaction->outputJsonValue($value);
            }

            $requiresSeparator = true;
            $this->propertyValues->next();
        }

        $transaction->outputJsonObjectEnd();
    }

    /**
     * Get the ID of this entity
     * @return PropertyValue|null PropertyValue representing the entity ID
     */
    public function getEntityId(): ?PropertyValue
    {
        $key = $this->getType()->getKey();
        return $this->propertyValues[$key];
    }

    /**
     * Set the ID of this entity
     * @param  mixed  $id  ID
     * @return $this
     */
    public function setEntityId($id): self
    {
        $key = $this->getType()->getKey();
        $type = $key->getType();

        $propertyValue = $this->newPropertyValue();
        $propertyValue->setProperty($key);
        $propertyValue->setValue($type->instance($id));
        $this->propertyValues[] = $propertyValue;

        return $this;
    }

    /**
     * Get the entity set that contains this entity
     * @return EntitySet|null
     */
    public function getEntitySet(): ?EntitySet
    {
        return $this->entitySet;
    }

    /**
     * Add a property value to this entity
     * @param  PropertyValue  $propertyValue  Property value
     * @return $this
     */
    public function addProperty(PropertyValue $propertyValue): self
    {
        $propertyValue->setEntity($this);
        $this->propertyValues[] = $propertyValue;

        return $this;
    }

    /**
     * Generate a new property value attached to this entity
     * @return PropertyValue Property value
     */
    public function newPropertyValue(): PropertyValue
    {
        $pv = new PropertyValue();
        $pv->setEntity($this);
        return $pv;
    }

    /**
     * Get all property values attached to this entity
     * @return ObjectArray Property values
     */
    public function getPropertyValues(): ObjectArray
    {
        return $this->propertyValues;
    }

    /**
     * Get a property value attached to this entity
     * @param  Property  $property  Property
     * @return Primitive|null Property value
     */
    public function getPropertyValue(Property $property): ?Primitive
    {
        return $this->propertyValues[$property]->getValue();
    }

    /**
     * Whether the provided property value exists on this entity
     * @param  mixed  $offset  Property name
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->propertyValues->exists($offset);
    }

    /**
     * Get a property value from this entity
     * @param  mixed  $offset  Property name
     * @return PropertyValue Property value
     */
    public function offsetGet($offset)
    {
        return $this->propertyValues->get($offset);
    }

    /**
     * Create a new property value on this entity
     * @param  mixed  $offset  Property name
     * @param  mixed  $value  Property value
     */
    public function offsetSet($offset, $value)
    {
        $property = $this->getType()->getProperty($offset);
        $propertyValue = $this->newPropertyValue();
        $propertyValue->setProperty($property);
        $propertyValue->setValue($property->getType()->instance($value));
        $this->addProperty($propertyValue);
    }

    /**
     * Remove a property value from this entity
     * @param  mixed  $offset  Property name
     */
    public function offsetUnset($offset)
    {
        $this->propertyValues->drop($offset);
    }

    /**
     * Get the metadata for this expanded entity
     * @param  Transaction  $transaction  Related transaction
     * @param  PropertyValue  $propertyValue  Navigation property value
     * @return MetadataContainer Metadata container
     */
    public function getExpansionMetadata(Transaction $transaction, PropertyValue $propertyValue): MetadataContainer
    {
        $propertyMetadata = $transaction->getMetadata()->getContainer();
        $propertyMetadata->setPrefix($propertyValue->getProperty()->getName());
        $propertyMetadata['navigationLink'] = $this->getResourceUrl($transaction).'/'.$propertyValue->getProperty()->getName();

        if ($propertyValue->getValue() instanceof EntitySet) {
            $set = $propertyValue->getEntitySetValue();
            $transaction = $set->getTransaction();

            $count = $set->count();

            if ($transaction->getCount()->hasValue()) {
                $propertyMetadata['count'] = $count;
            }

            $top = $transaction->getTop();
            $skip = $transaction->getSkip();

            if ($top->hasValue() && ($top->getValue() + ($skip->getValue() ?: 0) < $count)) {
                $np = $transaction->getQueryParams();
                $np['$skip'] = $top->getValue() + ($skip->getValue() ?: 0);
                $propertyMetadata['nextLink'] = Url::http_build_url(
                    $propertyMetadata['navigationLink'],
                    [
                        'query' => http_build_query(
                            $np,
                            null,
                            '&',
                            PHP_QUERY_RFC3986
                        ),
                    ],
                    Url::HTTP_URL_JOIN_QUERY
                );
            }
        }

        return $propertyMetadata;
    }

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface {
        return $argument;
    }

    /**
     * Get the context URL for this entity
     * @param  Transaction  $transaction  Related transaction
     * @return string Context URL
     */
    public function getContextUrl(Transaction $transaction): string
    {
        if ($this->usesReferences()) {
            return $transaction->getContextUrl().'#$ref';
        }

        if ($this->entitySet) {
            $url = $this->entitySet->getContextUrl($transaction);

            return $url.'/$entity';
        }

        $url = $this->type->getContextUrl($transaction);

        $properties = $transaction->getContextUrlProperties();

        if ($properties) {
            $url .= sprintf('(%s)', join(',', $properties));
        }

        return $url;
    }

    /**
     * Get the resource URL for this entity
     * @param  Transaction  $transaction  Related transaction
     * @return string Resource URL
     */
    public function getResourceUrl(Transaction $transaction): string
    {
        if (!$this->entitySet) {
            throw new InternalServerErrorException(
                'no_entity_set',
                'Entity is only a resource as part of an entity set'
            );
        }

        if (!$this->getEntityId()) {
            throw new InternalServerErrorException(
                'no_entity_id',
                'Entity is only a resource if it has an ID',
            );
        }

        return sprintf(
            '%s(%s)',
            $this->entitySet->getResourceUrl($transaction),
            $this->getEntityId()->getPrimitiveValue()->toUrl()
        );
    }

    /**
     * Delete this entity
     * @param  Transaction  $transaction  Related transaction
     * @param  ContextInterface|null  $context  Current context
     * @return Response Client response
     */
    public function delete(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        $entitySet = $this->entitySet;

        if (!$entitySet instanceof DeleteInterface) {
            throw new NotImplementedException('entityset_cannot_delete', 'This entity set cannot delete');
        }

        Gate::check(Gate::DELETE, $this, $transaction);

        $entitySet->delete($this->getEntityId());

        throw new NoContentException('deleted', 'Content was deleted');
    }

    /**
     * Update this entity
     * @param  Transaction  $transaction  Related transaction
     * @param  ContextInterface|null  $context  Current context
     * @return Response Client response
     */
    public function patch(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        $entitySet = $this->entitySet;

        if (!$entitySet instanceof UpdateInterface) {
            throw new NotImplementedException('entityset_cannot_update', 'This entity set cannot update');
        }

        Gate::check(Gate::UPDATE, $this, $transaction);

        $entity = $entitySet->update($this->getEntityId());

        return $entity->get($transaction, $context);
    }

    /**
     * Read this entity
     * @param  Transaction  $transaction  Related transaction
     * @param  ContextInterface|null  $context  Current context
     * @return Response Client response
     */
    public function get(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        Gate::check(Gate::READ, $this, $transaction);

        $context = $context ?: $this;

        $this->metadata = $transaction->getMetadata()->getContainer();
        $this->metadata['context'] = $context->getContextUrl($transaction);

        $response = $transaction->getResponse();

        $response->headers->set('etag', $this->getETag());

        return $response->setCallback(function () use ($transaction) {
            $this->emit($transaction);
        });
    }

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        if ($this->transaction) {
            $transaction = $this->transaction->replaceQueryParams($transaction);
        }

        switch ($transaction->getMethod()) {
            case Request::METHOD_PATCH:
            case Request::METHOD_PUT:
                return $this->patch($transaction, $context);

            case Request::METHOD_DELETE:
                return $this->delete($transaction, $context);

            case Request::METHOD_GET:
                return $this->get($transaction, $context);
        }

        throw new MethodNotAllowedException();
    }

    /**
     * Generate an entity from an array of key/values
     * @param  array  $array  Key/value array
     * @return $this
     */
    public function fromArray(array $array): self
    {
        foreach ($array as $key => $value) {
            $this[$key] = $value;
        }

        return $this;
    }

    /**
     * Get the ETag for this entity
     * @return string ETag
     */
    public function getETag(): string
    {
        $input = [];

        /** @var PropertyValue $propertyValue */
        foreach ($this->propertyValues as $propertyValue) {
            $property = $propertyValue->getProperty();

            if ($property instanceof DeclaredProperty) {
                $input[$property->getName()] = $propertyValue->getPrimitiveValue();
            }
        }

        return ETag::hash($input);
    }

    /**
     * Get the entity type of this entity
     * @return EntityType Entity type
     */
    public function getType(): EntityType
    {
        return $this->type;
    }

    /**
     * Set the entity type of this entity
     * @param  EntityType  $type  Entity type
     * @return $this
     */
    public function setType(EntityType $type): self
    {
        $this->type = $type;
        return $this;
    }
}
