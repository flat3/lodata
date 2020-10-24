<?php

namespace Flat3\Lodata;

use ArrayAccess;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\ETagException;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Interfaces\ArgumentInterface;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\EmitInterface;
use Flat3\Lodata\Interfaces\EntityTypeInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;

class Entity implements ResourceInterface, EntityTypeInterface, ContextInterface, ArrayAccess, EmitInterface, PipeInterface, ArgumentInterface
{
    /** @var ObjectArray $properties */
    private $properties;

    /** @var EntitySet $entitySet */
    private $entitySet;

    /** @var Transaction $transaction */
    private $transaction;

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

    public function emit(): void
    {
        /** @var DynamicProperty $dynamicProperty */
        foreach ($this->getType()->getDynamicProperties() as $dynamicProperty) {
            $dynamicPropertyType = $dynamicProperty->getType();
            $propertyValue = $this->newPropertyValue();
            $propertyValue->setProperty($dynamicProperty);
            $value = call_user_func([$dynamicProperty, 'invoke'], $this);

            if (
                !is_a($value, $dynamicPropertyType->getFactory(), true) ||
                $value === null && $dynamicPropertyType instanceof PrimitiveType && !$dynamicPropertyType->isNullable()
            ) {
                throw new InternalServerErrorException(
                    'invalid_dynamic_property_type',
                    sprintf(
                        'The dynamic property %s did not return a value of its defined type %s',
                        $dynamicProperty->getName(),
                        $dynamicPropertyType->getIdentifier()
                    )
                );
            }

            $propertyValue->setValue($value);
            $this->addProperty($propertyValue);
        }

        $expand = $this->transaction->getExpand();
        $expansionRequests = $expand->getExpansionRequests($this->getType());

        foreach ($expansionRequests as $expansionRequest) {
            $propertyValue = $this->newPropertyValue();
            $navigationProperty = $expansionRequest->getNavigationProperty();
            $propertyValue->setProperty($navigationProperty);

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

            $expansionTransaction = clone $this->transaction;
            $expansionTransaction->setRequest($expansionRequest);

            /** @var PropertyValue $keyPrimitive */
            $keyPrimitive = $this->properties->get($targetConstraint->getProperty());
            if ($keyPrimitive->getValue()->get() === null) {
                continue;
            }

            $referencedProperty = $targetConstraint->getReferencedProperty();
            $targetKey = new PropertyValue();
            $targetKey->setProperty($referencedProperty);
            $targetKey->setValue($keyPrimitive->getValue());

            $expansionSet = $targetEntitySet->asInstance($expansionTransaction);
            $expansionSet->setKey($targetKey);
            $propertyValue->setValue($expansionSet);

            $this->addProperty($propertyValue);
        }

        $transaction = $this->transaction;
        $transaction->outputJsonObjectStart();

        if ($this->metadata) {
            $transaction->outputJsonKV($this->metadata);

            if ($this->properties->hasEntries()) {
                $transaction->outputJsonSeparator();
            }
        }

        if ($this->properties->hasEntries()) {
            $this->properties->rewind();

            while ($this->properties->valid()) {
                /** @var PropertyValue $propertyValue */
                $propertyValue = $this->properties->current();

                if ($propertyValue->shouldEmit($transaction)) {
                    $key = $propertyValue->getProperty()->getName();
                    $value = $propertyValue->getValue();

                    $transaction->outputJsonKey($key);
                    $value->setTransaction($transaction);
                    $value->emit();

                    $this->properties->next();

                    if ($this->properties->valid() && $this->properties->current()->shouldEmit($transaction)) {
                        $transaction->outputJsonSeparator();
                    }
                } else {
                    $this->properties->next();
                }
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

    public function getProperties(): ObjectArray
    {
        return $this->properties;
    }

    public function getValue(Property $property): ?Primitive
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
        string $currentComponent,
        ?string $nextComponent,
        ?PipeInterface $argument
    ): ?PipeInterface {
        if (!$argument instanceof Entity) {
            throw new PathNotHandledException();
        }

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

        return sprintf('%s(%s)', $this->entitySet->getResourceUrl(), $this->getEntityId()->getValue()->toUrl());
    }

    public function setTransaction(Transaction $transaction): self
    {
        $this->transaction = $transaction;
        return $this;
    }

    public function response(): Response
    {
        $transaction = $this->transaction;

        $transaction->configureJsonResponse();

        $metadata = [
            'context' => $this->getContextUrl(),
        ];

        $this->metadata = $transaction->getMetadata()->filter($metadata);

        return $transaction->getResponse()->setCallback(function () {
            $this->emit();
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
