<?php

declare(strict_types=1);

namespace Flat3\Lodata\Type;

use ArrayAccess;
use ArrayObject;
use Flat3\Lodata\Annotation\Record;
use Flat3\Lodata\ComplexValue;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Helper\CollectionType;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Helper\Identifier;
use Flat3\Lodata\Helper\JSON;
use Flat3\Lodata\Interfaces\SerializeInterface;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Property;
use Flat3\Lodata\Type;
use Illuminate\Contracts\Support\Arrayable;
use TypeError;

/**
 * Collection
 * @package Flat3\Lodata\Type
 */
class Collection extends Primitive implements ArrayAccess
{
    /** @var Primitive[]|\Illuminate\Support\Collection $value */
    protected $value = null;

    /** @var CollectionType $type */
    protected $type;

    public function __construct($value = null)
    {
        $this->value = collect();
        $this->setCollectionType(new CollectionType);
        parent::__construct($value);
    }

    public function toUrl(): string
    {
        return JSON::encode($this->toJson());
    }

    public function toJson()
    {
        return array_map(function (Primitive $value) {
            return $value->toJson();
        }, $this->value);
    }

    public function toMixed(): ?array
    {
        return $this->value->map(function (SerializeInterface $value) {
            return $value->toMixed();
        })->toArray();
    }

    /**
     * Get the type of this collection
     * @return CollectionType
     */
    public function getCollectionType(): CollectionType
    {
        return $this->type;
    }

    /**
     * Set the type of this collection
     * @param  CollectionType  $type
     * @return $this
     */
    public function setCollectionType(CollectionType $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the underlying type of this collection's type
     * @return Type
     */
    public function getUnderlyingType(): Type
    {
        return $this->getCollectionType()->getUnderlyingType();
    }

    /**
     * Set the underlying type of this collection's type
     * @param  Type  $underlyingType  Type
     * @return $this
     */
    public function setUnderlyingType(Type $underlyingType): self
    {
        $this->getCollectionType()->setUnderlyingType($underlyingType);

        return $this;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->value[$offset]);
    }

    public function offsetGet($offset): Primitive
    {
        return $this->value[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        if (!$value instanceof Primitive && !$value instanceof ComplexValue && !$value instanceof Record) {
            $type = $this->getUnderlyingType();

            if ($type instanceof Untyped) {
                $type = Type::fromInternalValue($value);
            }

            $value = $type->instance($value);
        }

        if ($offset !== null) {
            $this->value[$offset] = $value;
        } else {
            $this->value[] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->value[$offset]);
    }

    /**
     * Replace the value of this collection
     * @param  array|Arrayable  $value
     * @return $this
     */
    public function set($value = [])
    {
        if (null === $value) {
            $value = [];
        }

        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        if ($value instanceof ArrayObject) {
            $value = $value->getArrayCopy();
        }

        if (!is_array($value)) {
            throw new TypeError('The value provided for the collection was not formed as an array');
        }

        foreach ($value as $item) {
            $this[] = $item;
        }

        return $this;
    }

    public function getIdentifier(): Identifier
    {
        return $this->type->getIdentifier();
    }

    /**
     * Get the context URL of this collection
     * @param  Transaction  $transaction  Related transaction
     * @return string Context URL
     */
    public function getContextUrl(Transaction $transaction): string
    {
        return sprintf("%s#Collection(%s)", $transaction->getContextUrl(), $this->type->getIdentifier());
    }

    public function emitJson(Transaction $transaction): void
    {
        $transaction->outputJsonArrayStart();

        $iterator = $this->value->getIterator();

        while ($iterator->current()) {
            $value = $iterator->current();
            $value->emitJson($transaction);
            $iterator->next();

            if ($iterator->current()) {
                $transaction->outputJsonSeparator();
            }
        }

        $transaction->outputJsonArrayEnd();
    }

    public function getOpenAPISchema(?Property $property = null): array
    {
        return [
            'type' => Constants::oapiArray,
            'items' => $this->getUnderlyingType()->instance()->getOpenAPISchema($property),
        ];
    }
}
