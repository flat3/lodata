<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Type\Property;

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

    public function __construct($name, EntityType $type)
    {
        if (!$type->getKey()) {
            throw new InternalServerErrorException(
                'missing_entity_type_key',
                'The specified entity type must have a key defined'
            );
        }

        if ($name instanceof IdentifierInterface) {
            $name = $name->getName();
        }

        parent::__construct($name, $type);

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
}
