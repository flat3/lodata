<?php

namespace Flat3\OData;

use Flat3\OData\Interfaces\EmitInterface;
use Flat3\OData\Interfaces\TypeInterface;
use Flat3\OData\Type\PrimitiveType;
use RuntimeException;

class Primitive implements TypeInterface, EmitInterface
{
    /** @var Entity $entity */
    private $entity;

    /** @var Property $property */
    private $property;

    /** @var PrimitiveType $value */
    private $value;

    public function __construct($value, Property $property, ?Entity $entity = null)
    {
        $this->property = $property;

        if ($value instanceof Primitive) {
            $value = $value->getValue();
        }

        $this->value = $property->getType()::factory($value);
        $this->entity = $entity;
    }

    public function getValue()
    {
        return $this->value->get();
    }

    public function getEntity(): Entity
    {
        return $this->entity;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function toUrl(): string
    {
        return $this->value->toUrl();
    }

    public function toJsonIeee754(): ?string
    {
        return $this->value->toJsonIeee754();
    }

    public function toJson()
    {
        return $this->value->toJson();
    }

    public function getType(): Type
    {
        return $this->property->getType();
    }

    public function getTypeName(): string
    {
        return $this->property->getTypeName();
    }

    public function setType(Type $type)
    {
        throw new RuntimeException('Cannot set type of a primitive, the type comes from the property');
    }

    public function emit(Transaction $transaction)
    {
        $transaction->outputJsonKV(
            [
                'value' => $this
            ]
        );
    }
}
