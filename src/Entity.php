<?php

namespace Flat3\OData;

use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Exception\StoreException;
use Flat3\OData\Interfaces\IdentifierInterface;
use Flat3\OData\Interfaces\TypeInterface;
use Flat3\OData\Property\Constraint;
use Flat3\OData\Traits\HasIdentifier;
use Flat3\OData\Traits\HasType;
use Flat3\OData\Type\PrimitiveType;

abstract class Entity implements IdentifierInterface, TypeInterface
{
    use HasIdentifier;
    use HasType;

    /** @var PrimitiveType $entityId */
    private $entityId;

    /** @var ObjectArray $primitives */
    private $primitives;

    /** @var Store $store */
    private $store;

    public function __construct(?Store $store = null)
    {
        $this->store = $store;
        $this->primitives = new ObjectArray();
    }

    public function writeToResponse(Transaction $transaction)
    {
        $entityId = $this->getEntityId();
        $expand = $transaction->getExpand();

        $metadata = [];
        if ($entityId) {
            $metadata['id'] = $transaction->getEntityResourceUrl($this->store, $entityId->toUrl());
        }

        $metadata = $transaction->getMetadata()->filter($metadata);

        $expansionRequests = $expand->getExpansionRequests($this->store->getType());

        if ($metadata) {
            $transaction->outputJsonKV($metadata);

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
                    $transaction->outputJsonKV([$primitive->getProperty()->getIdentifier()->get() => $primitive]);

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

            $binding = $this->store->getBindingByNavigationProperty($navigationProperty);
            $targetStore = $binding->getTarget();
            $targetStoreType = $targetStore->getType();

            $targetConstraint = null;
            /** @var Constraint $constraint */
            foreach ($navigationProperty->getConstraints() as $constraint) {
                if ($targetStoreType->getProperty($constraint->getReferencedProperty()) && $this->store->getTypeProperty($constraint->getProperty())) {
                    $targetConstraint = $constraint;
                    break;
                }
            }

            if (!$targetConstraint) {
                throw new BadRequestException(
                    'no_expansion_constraint',
                    sprintf(
                        'No applicable constraint could be found between sets %s and %s for expansion',
                        $this->store->getIdentifier()->get(),
                        $targetStore->getIdentifier()->get()
                    )
                );
            }

            $expansionTransaction = clone $transaction;
            $expansionTransaction->setRequest($expansionRequest);

            /** @var Primitive $keyPrimitive */
            $keyPrimitive = $this->primitives->get($targetConstraint->getProperty());
            if ($keyPrimitive->getInternalValue() === null) {
                $expansionRequests->next();
                continue;
            }

            $referencedProperty = $targetConstraint->getReferencedProperty();
            $targetKey = new Primitive($keyPrimitive, $referencedProperty);

            if ($referencedProperty === $targetStore->getTypeKey()) {
                $entity = $targetStore->getEntity($expansionTransaction, $targetKey);
                $transaction->outputJsonKey($navigationProperty);

                if ($entity) {
                    $transaction->outputJsonObjectStart();
                    $entity->writeToResponse($expansionTransaction);
                    $transaction->outputJsonObjectEnd();
                } else {
                    $transaction->outputJsonValue(null);
                }
            } else {
                $transaction->outputJsonKey($navigationProperty);
                $transaction->outputJsonArrayStart();
                $entitySet = $targetStore->getEntitySet($expansionTransaction, $targetKey);
                $entitySet->writeToResponse($expansionTransaction);
                $transaction->outputJsonArrayEnd();
            }

            $expansionRequests->next();
            if ($expansionRequests->valid()) {
                $transaction->outputJsonSeparator();
            }
        }
    }

    public function getEntityId(): ?PrimitiveType
    {
        return $this->entityId;
    }

    public function setEntityId(PrimitiveType $entityId)
    {
        $this->entityId = $entityId;
    }

    public function getStore(): Store
    {
        return $this->store;
    }

    public function getPrimitives(): ObjectArray
    {
        return $this->primitives;
    }

    public function addPrimitive($value, Property $property): void
    {
        if (null === $value && !$property->isNullable()) {
            throw new StoreException(
                'The store provided a null value that cannot be added for this property type: '.$property->getIdentifier()->get(),
            );
        }

        if ($property === $this->store->getTypeKey()) {
            $this->setEntityIdValue($value);
        }

        $this->primitives[$property] = $this->primitiveFactory($value, $property);
    }

    public function setEntityIdValue($entityId)
    {
        $this->setEntityId($this->store->getType()->getKey()->getType()::factory($entityId));
    }

    public function primitiveFactory($value, Property $property): Primitive
    {
        return new Primitive($value, $property, $this);
    }

    public function getPrimitive(Property $property): ?Primitive
    {
        return $this->primitives[$property];
    }
}
