<?php

namespace Flat3\Lodata;

use ArrayAccess;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\ETagException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Interfaces\ArgumentInterface;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\EmitInterface;
use Flat3\Lodata\Interfaces\EntityTypeInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Transaction\Expand;

class Entity implements ResourceInterface, EntityTypeInterface, ContextInterface, ArrayAccess, EmitInterface, PipeInterface, ArgumentInterface
{
    /** @var ObjectArray $propertyValues */
    private $propertyValues;

    /** @var EntitySet $entitySet */
    private $entitySet;

    /** @var Transaction $transaction */
    private $transaction;

    /** @var EntityType $type */
    private $type;

    protected $metadata = [];

    public function __construct()
    {
        $this->propertyValues = new ObjectArray();
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
        $dynamicProperties = $this->getType()->getDynamicProperties();
        $expand = $transaction->getExpand();
        $expansionRequests = $expand->getExpansionRequests($this->getType());

        if ($this->metadata) {
            $transaction->outputJsonKV($this->metadata);

            if ($this->propertyValues->hasEntries()) {
                $transaction->outputJsonSeparator();
            }
        }

        if ($this->propertyValues->hasEntries()) {
            $this->propertyValues->rewind();

            while ($this->propertyValues->valid()) {
                /** @var PropertyValue $propertyValue */
                $propertyValue = $this->propertyValues->current();

                if ($propertyValue->shouldEmit($transaction)) {
                    $transaction->outputJsonKey($propertyValue->getProperty()->getName());
                    $transaction->outputJsonValue($propertyValue);

                    $this->propertyValues->next();

                    if ($this->propertyValues->valid() && $this->propertyValues->current()->shouldEmit($transaction)) {
                        $transaction->outputJsonSeparator();
                    }
                } else {
                    $this->propertyValues->next();
                }
            }

            if ($dynamicProperties->hasEntries() || $expansionRequests->hasEntries()) {
                $transaction->outputJsonSeparator();
            }
        }

        if ($dynamicProperties->hasEntries()) {
            $dynamicProperties->rewind();

            while ($dynamicProperties->valid()) {
                /** @var DynamicProperty $property */
                $property = $dynamicProperties->current();

                if ($propertyValue->shouldEmit($transaction)) {
                    $transaction->outputJsonKey($property->getName());

                    $result = call_user_func_array([$property, 'invoke'], [$this, $transaction]);

                    if (!is_a($result, $property->getType()->getFactory(),
                            true) || $result === null && $property->getType() instanceof PrimitiveType && !$property->getType()->isNullable()) {
                        throw new InternalServerErrorException('invalid_dynamic_property_type',
                            sprintf('The dynamic property %s did not return a value of its defined type',
                                $property->getName()));
                    }

                    $transaction->outputJsonValue($result);

                    $dynamicProperties->next();

                    if ($dynamicProperties->valid() && $dynamicProperties->current()->shouldEmit($transaction)) {
                        $transaction->outputJsonSeparator();
                    }
                } else {
                    $dynamicProperties->next();
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
                        $this->entitySet->getIdentifier(),
                        $targetEntitySet->getIdentifier()
                    )
                );
            }

            $expansionTransaction = clone $transaction;
            $expansionTransaction->setRequest($expansionRequest);

            /** @var PropertyValue $keyPrimitive */
            $keyPrimitive = $this->propertyValues->get($targetConstraint->getProperty());
            if ($keyPrimitive->getValue()->get() === null) {
                $expansionRequests->next();
                continue;
            }

            $referencedProperty = $targetConstraint->getReferencedProperty();
            $targetKey = new PropertyValue();
            $targetKey->setProperty($referencedProperty);
            $targetKey->setValue($keyPrimitive->getValue());

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

    public function getEntityId(): ?PropertyValue
    {
        $key = $this->getType()->getKey();
        return $this->propertyValues[$key];
    }

    public function setEntityId($id): self
    {
        $key = $this->getType()->getKey();
        $type = $key->getType();

        $propertyValue = new PropertyValue();
        $propertyValue->setProperty($key);
        $propertyValue->setValue($type->instance($id));
        $this->propertyValues[] = $propertyValue;

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

        $propertyValue = new PropertyValue();
        $propertyValue->setProperty($property);
        $propertyValue->setEntity($this);
        $propertyValue->setValue($property->getType()->instance($value));

        $this->propertyValues[] = $propertyValue;

        return $this;
    }

    public function getPropertyValues(): ObjectArray
    {
        return $this->propertyValues;
    }

    public function getValue(Property $property): ?Primitive
    {
        return $this->propertyValues[$property]->getValue();
    }

    public function offsetExists($offset)
    {
        return $this->propertyValues->exists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->propertyValues->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->setPrimitive($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->propertyValues->drop($offset);
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
        $instance = $this->propertyValues->sliceByClass(DeclaredProperty::class);

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
