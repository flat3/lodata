<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Exception\Protocol\BadRequestException;
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

    public function generatePropertyValue(Entity $entity, NavigationRequest $navigationRequest): PropertyValue
    {
        $propertyValue = $entity->newPropertyValue();
        $propertyValue->setProperty($this);

        $binding = $entity->getEntitySet()->getBindingByNavigationProperty($this);
        $targetEntitySet = $binding->getTarget();
        $targetEntitySetType = $targetEntitySet->getType();

        $targetConstraint = null;
        /** @var ReferentialConstraint $constraint */
        foreach ($this->getConstraints() as $constraint) {
            if ($targetEntitySetType->getProperty($constraint->getReferencedProperty()) && $entity->getEntitySet()->getType()->getProperty($constraint->getProperty())) {
                $targetConstraint = $constraint;
                break;
            }
        }

        if (!$targetConstraint) {
            throw new BadRequestException(
                'no_expansion_constraint',
                sprintf(
                    'No applicable constraint could be found between sets %s and %s for expansion',
                    $entity->getEntitySet()->getIdentifier(),
                    $targetEntitySet->getIdentifier()
                )
            );
        }

        $expansionTransaction = clone $entity->getTransaction();
        $expansionTransaction->setRequest($navigationRequest);

        /** @var PropertyValue $keyPrimitive */
        $keyPrimitive = $entity->getPropertyValues()->get($targetConstraint->getProperty());
        if ($keyPrimitive->getValue()->get() === null) {
            throw new InternalServerErrorException('missing_expansion_key', 'The target constraint key is missing');
        }

        $referencedProperty = $targetConstraint->getReferencedProperty();
        $targetKey = new PropertyValue();
        $targetKey->setProperty($referencedProperty);
        $targetKey->setValue($keyPrimitive->getValue());

        $expansionSet = $targetEntitySet->asInstance($expansionTransaction);
        $expansionSet->setKey($targetKey);
        $propertyValue->setValue($expansionSet);

        return $propertyValue;
    }
}
