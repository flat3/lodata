<?php

namespace Flat3\OData;

use ArrayAccess;
use Flat3\OData\Controller\Response;
use Flat3\OData\Controller\Transaction;
use Flat3\OData\Exception\Internal\ETagException;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Exception\Protocol\InternalServerErrorException;
use Flat3\OData\Helper\ObjectArray;
use Flat3\OData\Interfaces\ArgumentInterface;
use Flat3\OData\Interfaces\ContextInterface;
use Flat3\OData\Interfaces\EmitInterface;
use Flat3\OData\Interfaces\EntityTypeInterface;
use Flat3\OData\Interfaces\PipeInterface;
use Flat3\OData\Interfaces\ResourceInterface;
use Flat3\OData\Traits\HasEntityType;
use Flat3\OData\Transaction\Expand;
use Flat3\OData\Type\Property;

class Entity implements ResourceInterface, EntityTypeInterface, ContextInterface, ArrayAccess, EmitInterface, PipeInterface, ArgumentInterface
{
    use HasEntityType;

    /** @var ObjectArray $primitives */
    private $primitives;

    /** @var EntitySet $entitySet */
    private $entitySet;

    /** @var Transaction $transaction */
    private $transaction;

    protected $metadata = [];

    public function __construct()
    {
        $this->primitives = new ObjectArray();
    }

    public function setEntitySet(EntitySet $entitySet): self
    {
        $this->entitySet = $entitySet;
        $this->type = $entitySet->getType();
        return $this;
    }

    public function emit(Transaction $transaction): void
    {
        $transaction->outputJsonObjectStart();

        $expand = $transaction->getExpand();
        $expansionRequests = $expand->getExpansionRequests($this->getType());

        if ($this->metadata) {
            $transaction->outputJsonKV($this->metadata);

            if ($this->primitives->hasEntries()) {
                $transaction->outputJsonSeparator();
            }
        }

        if ($this->primitives->hasEntries()) {
            $this->primitives->rewind();

            while ($this->primitives->valid()) {
                /** @var PrimitiveType $primitive */
                $primitive = $this->primitives->current();

                if ($transaction->shouldEmitPrimitive($primitive)) {
                    $transaction->outputJsonKey($primitive->getProperty()->getName());
                    $transaction->outputJsonValue($primitive);

                    $this->primitives->next();

                    if ($this->primitives->valid() && $transaction->shouldEmitPrimitive($this->primitives->current())) {
                        $transaction->outputJsonSeparator();
                    }
                } else {
                    $this->primitives->next();
                }
            }

            if ($expansionRequests->hasEntries()) {
                $transaction->outputJsonSeparator();
            }
        }

        /** @var Expand $expansionRequest */
        $expansionRequests->rewind();
        while ($expansionRequests->valid()) {
            $expansionRequest = $expansionRequests->current();

            $navigationProperty = $expansionRequest->getNavigationProperty();

            $binding = $this->entitySet->getBindingByNavigationProperty($navigationProperty);
            $targetEntitySet = $binding->getTarget();
            $targetEntitySetType = $targetEntitySet->getType();

            $targetConstraint = null;
            /** @var ReferentialConstraint $constraint */
            foreach ($navigationProperty->getConstraints() as $constraint) {
                if ($targetEntitySetType->getProperty($constraint->getReferencedProperty()) && $this->entitySet->getType()->getProperty($constraint->getProperty())) {
                    $targetConstraint = $constraint;
                    break;
                }
            }

            if (!$targetConstraint) {
                throw new BadRequestException(
                    'no_expansion_constraint',
                    sprintf(
                        'No applicable constraint could be found between sets %s and %s for expansion',
                        $this->entitySet->getName(),
                        $targetEntitySet->getName()
                    )
                );
            }

            $expansionTransaction = $transaction->subTransaction($expansionRequest);

            /** @var PrimitiveType $keyPrimitive */
            $keyPrimitive = $this->primitives->get($targetConstraint->getProperty());
            if ($keyPrimitive->get() === null) {
                $expansionRequests->next();
                continue;
            }

            $referencedProperty = $targetConstraint->getReferencedProperty();
            $targetKey = clone $keyPrimitive;
            $targetKey->setProperty($referencedProperty);

            if ($referencedProperty === $targetEntitySet->getType()->getKey()) {
                $expansionSet = $targetEntitySet->asInstance($transaction);
                $entity = $expansionSet->read($targetKey);
                $transaction->outputJsonKey($navigationProperty);

                if ($entity) {
                    $transaction->outputJsonObjectStart();
                    $entity->emit($expansionTransaction);
                    $transaction->outputJsonObjectEnd();
                } else {
                    $transaction->outputJsonValue(null);
                }
            } else {
                $transaction->outputJsonKey($navigationProperty);
                $entitySet = $targetEntitySet->asInstance($expansionTransaction)->setKey($targetKey);
                $entitySet->emit($expansionTransaction);
            }

            $expansionRequests->next();
            if ($expansionRequests->valid()) {
                $transaction->outputJsonSeparator();
            }
        }

        $transaction->outputJsonObjectEnd();
    }

    public function getEntityId(): ?PrimitiveType
    {
        $key = $this->getType()->getKey();
        return $this->primitives[$key];
    }

    public function setEntityId($id): self
    {
        $key = $this->getType()->getKey();
        /** @var PrimitiveType $type */
        $type = clone $key->getType();
        $type->set($id);
        $type->setProperty($key);
        $this->primitives[$key] = $type;
        return $this;
    }

    public function getEntitySet(): ?EntitySet
    {
        return $this->entitySet;
    }

    public function setPrimitive($property, $value): self
    {
        if (is_string($property)) {
            $property = $this->getType()->getProperty($property);
        }

        if (!$property instanceof Property) {
            throw new InternalServerErrorException(
                'undefined_property',
                'The service attempted to access an undefined property'
            );
        }

        if (null === $value && !$property->isNullable()) {
            throw new InternalServerErrorException(
                'cannot_add_null_property',
                'The entity set provided a null value that cannot be added for this property type: '.$property->getName()
            );
        }

        $type = $property->getType();
        $primitive = new $type($value);
        $primitive->setProperty($property);
        $primitive->setEntity($this);

        $this->primitives[$property] = $primitive;

        return $this;
    }

    public function getPrimitives(): ObjectArray
    {
        return $this->primitives;
    }

    public function getPrimitive(Property $property): ?PrimitiveType
    {
        return $this->primitives[$property];
    }

    public function offsetExists($offset)
    {
        return $this->primitives->exists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->primitives->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->setPrimitive($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->primitives->drop($offset);
    }

    public static function pipe(
        Transaction $transaction,
        string $currentComponent,
        ?string $nextComponent,
        ?PipeInterface $argument
    ): ?PipeInterface {
        return $argument;
    }

    public function getContextUrl(): string
    {
        if ($this->entitySet) {
            $url = $this->entitySet->getContextUrl();

            return $url.'/$entity';
        }

        $url = $this->type->getContextUrl();

        $properties = $this->transaction->getContextUrlProperties();

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

        return sprintf('%s(%s)', $this->entitySet->getResourceUrl(), $this->getEntityId()->toUrl());
    }

    public function response(Transaction $transaction): Response
    {
        $this->transaction = $transaction;
        $transaction->configureJsonResponse();

        $metadata = [
            'context' => $this->getContextUrl(),
        ];

        $this->metadata = $transaction->getMetadata()->filter($metadata);

        return $transaction->getResponse()->setCallback(function () use ($transaction, $metadata) {
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
        $instance = $this->primitives->sliceByClass(DeclaredProperty::class);

        if (array_diff($definition->keys(), $instance->keys())) {
            throw new ETagException();
        }

        return $instance->hash();
    }
}
