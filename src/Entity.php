<?php

namespace Flat3\Lodata;

use ArrayAccess;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\ETagException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\EmitInterface;
use Flat3\Lodata\Interfaces\EntityTypeInterface;
use Flat3\Lodata\Interfaces\Operation\ArgumentInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Traits\HasTransaction;
use Flat3\Lodata\Transaction\NavigationRequest;

class Entity implements ResourceInterface, EntityTypeInterface, ContextInterface, ArrayAccess, EmitInterface, PipeInterface, ArgumentInterface
{
    use HasTransaction;

    /** @var ObjectArray $properties */
    private $properties;

    /** @var EntitySet $entitySet */
    private $entitySet;

    /** @var EntityType $type */
    private $type;

    protected $metadata = [];

    public function __construct()
    {
        $this->properties = new ObjectArray();
    }

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

        /** @var DynamicProperty $dynamicProperty */
        foreach ($this->getType()->getDynamicProperties() as $dynamicProperty) {
            $dynamicProperty->generatePropertyValue($this);
        }

        /** @var NavigationProperty $navigationProperty */
        foreach ($this->getType()->getNavigationProperties() as $navigationProperty) {
            /** @var NavigationRequest $navigationRequest */
            $navigationRequest = $navigationRequests->get($navigationProperty->getName());

            if (!$navigationRequest) {
                continue;
            }

            $navigationPath = $navigationRequest->getBasePath();

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

        if ($this->metadata) {
            $transaction->outputJsonKV($this->metadata);

            if ($this->properties->hasEntries()) {
                $transaction->outputJsonSeparator();
            }
        }

        while ($this->properties->valid()) {
            /** @var PropertyValue $propertyValue */
            $propertyValue = $this->properties->current();

            if (!$propertyValue->shouldEmit($transaction)) {
                $this->properties->next();
                continue;
            }

            $transaction->outputJsonKey($propertyValue->getProperty()->getName());
            $propertyValue->getValue()->emit($transaction);

            $this->properties->next();

            if ($this->properties->valid() && $this->properties->current()->shouldEmit($transaction)) {
                $transaction->outputJsonSeparator();
            }
        }

        $transaction->outputJsonObjectEnd();
    }

    public function getEntityId(): ?PropertyValue
    {
        $key = $this->getType()->getKey();
        return $this->properties[$key];
    }

    public function setEntityId($id): self
    {
        $key = $this->getType()->getKey();
        $type = $key->getType();

        $propertyValue = $this->newPropertyValue();
        $propertyValue->setProperty($key);
        $propertyValue->setValue($type->instance($id));
        $this->properties[] = $propertyValue;

        return $this;
    }

    public function getEntitySet(): ?EntitySet
    {
        return $this->entitySet;
    }

    public function addProperty($property): self
    {
        $this->properties[] = $property;

        return $this;
    }

    public function newPropertyValue(): PropertyValue
    {
        $pv = new PropertyValue();
        $pv->setEntity($this);
        return $pv;
    }

    public function getPropertyValues(): ObjectArray
    {
        return $this->properties;
    }

    public function getPropertyValue(Property $property): ?Primitive
    {
        return $this->properties[$property]->getValue();
    }

    public function offsetExists($offset)
    {
        return $this->properties->exists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->properties->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $property = $this->getType()->getProperty($offset);
        $propertyValue = $this->newPropertyValue();
        $propertyValue->setProperty($property);
        $propertyValue->setValue($property->getType()->instance($value));
        $this->addProperty($propertyValue);
    }

    public function offsetUnset($offset)
    {
        $this->properties->drop($offset);
    }

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface {
        return $argument;
    }

    public function getContextUrl(Transaction $transaction): string
    {
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

    public function getResourceUrl(): string
    {
        if (!$this->entitySet) {
            throw new InternalServerErrorException(
                'no_entity_resource',
                'Entity is only a resource as part of an entity set'
            );
        }

        return sprintf('%s(%s)', $this->entitySet->getResourceUrl(),
            $this->getEntityId()->getPrimitiveValue()->toUrl());
    }

    public function response(Transaction $transaction): Response
    {
        $transaction = $this->transaction ?: $transaction;

        $metadata = [
            'context' => $this->getContextUrl($transaction),
        ];

        $this->metadata = $transaction->getMetadata()->filter($metadata);

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emit($transaction);
        });
    }

    public function fromArray(array $array): self
    {
        foreach ($array as $key => $value) {
            $this[$key] = $value;
        }

        return $this;
    }

    public function getETag(): string
    {
        $definition = $this->entitySet->getType()->getDeclaredProperties();
        $instance = $this->properties->sliceByClass(DeclaredProperty::class);

        if (array_diff($definition->keys(), $instance->keys())) {
            throw new ETagException();
        }

        return $instance->hash();
    }

    public function getType(): EntityType
    {
        return $this->type;
    }

    public function setType(EntityType $type): self
    {
        $this->type = $type;
        return $this;
    }
}
