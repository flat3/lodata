<?php

namespace Flat3\OData;

use ArrayAccess;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Exception\ResourceException;
use Flat3\OData\Interfaces\EmitInterface;
use Flat3\OData\Interfaces\EntityTypeInterface;
use Flat3\OData\Interfaces\IdentifierInterface;
use Flat3\OData\Interfaces\PipeInterface;
use Flat3\OData\Internal\ObjectArray;
use Flat3\OData\Property\Constraint;
use Flat3\OData\Traits\HasEntityType;
use Flat3\OData\Traits\HasIdentifier;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Entity implements IdentifierInterface, EntityTypeInterface, ArrayAccess, EmitInterface, PipeInterface
{
    use HasIdentifier;
    use HasEntityType;

    /** @var ObjectArray $primitives */
    private $primitives;

    /** @var EntitySet $entitySet */
    private $entitySet;

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
                /** @var Primitive $primitive */
                $primitive = $this->primitives->current();

                if ($transaction->shouldEmitPrimitive($primitive)) {
                    $transaction->outputJsonKey($primitive->getProperty()->getIdentifier()->get());
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

        /** @var Request\Expand $expansionRequest */
        $expansionRequests->rewind();
        while ($expansionRequests->valid()) {
            $expansionRequest = $expansionRequests->current();

            $navigationProperty = $expansionRequest->getNavigationProperty();

            $binding = $this->entitySet->getBindingByNavigationProperty($navigationProperty);
            $targetEntitySet = $binding->getTarget();
            $targetEntitySetType = $targetEntitySet->getType();

            $targetConstraint = null;
            /** @var Constraint $constraint */
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
                        $this->entitySet->getIdentifier()->get(),
                        $targetEntitySet->getIdentifier()->get()
                    )
                );
            }

            $expansionTransaction = $transaction->subTransaction($expansionRequest);

            /** @var Primitive $keyPrimitive */
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
                $entity = $expansionSet->getEntity($targetKey);
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

    public function getEntityId(): ?Primitive
    {
        $key = $this->getType()->getKey();
        return $this->primitives[$key];
    }

    public function getEntitySet(): EntitySet
    {
        return $this->entitySet;
    }

    public function setPrimitive($property, $value): self
    {
        if (is_string($property)) {
            $property = $this->getType()->getProperty($property);
        }

        if (!$property instanceof Property) {
            throw new ResourceException('The service attempted to access an undefined property');
        }

        if (null === $value && !$property->isNullable()) {
            throw new ResourceException(
                'The entity set provided a null value that cannot be added for this property type: '.$property->getIdentifier()->get(),
            );
        }

        $type = $property->getType();
        $primitive = new $type($value);
        $primitive->setProperty($property);
        $primitive->setEntity($this);

        $this->primitives[$property] = $primitive;

        return $this;
    }

    public function getPrimitive(Property $property): ?Primitive
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
        string $pathComponent,
        ?PipeInterface $argument
    ): ?PipeInterface {
        return $argument;
    }

    public function response(Transaction $transaction): StreamedResponse
    {
        $transaction->setContentTypeJson();

        $metadata = [];

        $select = $transaction->getSelect();

        if ($select->hasValue() && !$select->isStar()) {
            $metadata['context'] = $transaction->getProjectedEntityContextUrl($this->entitySet, $select->getValue());
        } else {
            if ($this->entitySet) {
                $metadata['context'] = $transaction->getEntityContextUrl($this->entitySet);
            } else {
                $metadata['context'] = $transaction->getTypeContextUrl($this->type);
            }
        }

        $entityId = $this->getEntityId();

        if ($entityId) {
            $metadata['id'] = $transaction->getEntityResourceUrl($this->entitySet, $entityId->toUrl());
        }

        $this->metadata = $transaction->getMetadata()->filter($metadata);

        return $transaction->getResponse()->setCallback(function () use ($transaction, $metadata) {
            $this->emit($transaction);
        });
    }
}
