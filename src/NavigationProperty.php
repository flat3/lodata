<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Transaction\NavigationRequest;

class NavigationProperty extends Property
{
    /** @var self $partner */
    protected $partner;

    /** @var ObjectArray $constraints */
    protected $constraints;

    /** @var bool $collection */
    protected $collection = false;

    /** @var bool $expandable */
    protected $expandable = true;

    public function __construct($name, EntityType $entityType)
    {
        if (!$entityType->getKey()) {
            throw new InternalServerErrorException(
                'missing_entity_type_key',
                'The specified entity type must have a key defined'
            );
        }

        if ($name instanceof IdentifierInterface) {
            $name = $name->getName();
        }

        parent::__construct($name, $entityType);

        $this->constraints = new ObjectArray();
    }

    public function isCollection(): bool
    {
        return $this->collection;
    }

    public function setCollection($collection): self
    {
        $this->collection = $collection;
        return $this;
    }

    public function isExpandable(): bool
    {
        return $this->expandable;
    }

    public function setExpandable(bool $expandable): self
    {
        $this->expandable = $expandable;

        return $this;
    }

    public function getPartner(): ?self
    {
        return $this->partner;
    }

    public function setPartner(self $partner): self
    {
        $this->partner = $partner;

        return $this;
    }

    public function addConstraint(ReferentialConstraint $constraint): self
    {
        $this->constraints[] = $constraint;

        return $this;
    }

    public function getConstraints(): ObjectArray
    {
        return $this->constraints;
    }

    public function generatePropertyValue(
        Transaction $transaction,
        NavigationRequest $navigationRequest,
        Entity $entity
    ): ?PropertyValue {
        $expansionTransaction = clone $transaction;
        $expansionTransaction->setRequest($navigationRequest);

        $propertyValue = $entity->newPropertyValue();
        $propertyValue->setProperty($this);

        $binding = $entity->getEntitySet()->getBindingByNavigationProperty($this);
        $targetEntitySet = $binding->getTarget();

        $expansionSet = clone $targetEntitySet;
        $expansionSet->setTransaction($expansionTransaction);

        if ($this->isCollection()) {
            $propertyValue->setValue($expansionSet);
            $expansionSet->setExpansionPropertyValue($propertyValue);
        } else {
            $expansionSingular = $expansionSet->current();
            $propertyValue->setValue($expansionSingular);
        }

        $entity->addProperty($propertyValue);

        return $propertyValue;
    }
}
