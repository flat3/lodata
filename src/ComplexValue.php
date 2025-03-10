<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use ArrayAccess;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\ETag;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Helper\PropertyValues;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\JsonInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ReferenceInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Interfaces\ResponseInterface;
use Flat3\Lodata\Interfaces\SerializeInterface;
use Flat3\Lodata\Traits\HasTransaction;
use Flat3\Lodata\Traits\UseReferences;
use Flat3\Lodata\Transaction\MetadataContainer;
use Flat3\Lodata\Transaction\NavigationRequest;
use Flat3\Lodata\Type\Untyped;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ComplexValue implements ArrayAccess, Arrayable, JsonInterface, ReferenceInterface, SerializeInterface, ResourceInterface, ResponseInterface, ContextInterface, PipeInterface
{
    use UseReferences;
    use HasTransaction;

    /**
     * Property values on this complex value instance
     * @var PropertyValues $propertyValues
     */
    protected $propertyValues;

    /**
     * The type of this complex value
     * @var ComplexType $type
     */
    protected $type;

    /**
     * The metadata about this complex value
     * @var MetadataContainer $metadata
     */
    protected $metadata = null;

    /**
     * @var PropertyValue $parent
     */
    protected $parent = null;

    public function __construct()
    {
        $this->propertyValues = new PropertyValues();
        $this->type = new Untyped();
    }

    /**
     * Set the parent property value of this complex value
     * @param  PropertyValue  $parent  Parent property
     * @return $this
     */
    public function setParent(PropertyValue $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get the parent property of this complex value
     * @return PropertyValue|null
     */
    public function getParent(): ?PropertyValue
    {
        return $this->parent;
    }

    /**
     * Get the type of this complex value
     * @return ComplexType Complex type
     */
    public function getType(): ComplexType
    {
        return $this->type;
    }

    /**
     * Set the type of this complex value
     * @param  ComplexType  $type  Type
     * @return $this
     */
    public function setType(ComplexType $type): self
    {
        $this->type = $type;

        if (!$type instanceof Untyped && !Lodata::getComplexType($type->getIdentifier())) {
            Lodata::add($type);
        }

        return $this;
    }

    /**
     * Whether the provided property value exists on this complex value
     * @param  mixed  $offset  Property name
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $this->propertyValues->exists($offset);
    }

    /**
     * Add a property value to this complex value
     * @param  PropertyValue  $propertyValue  Property value
     * @return $this
     */
    public function addPropertyValue(PropertyValue $propertyValue): self
    {
        $propertyValue->setParent($this);
        $this->propertyValues[] = $propertyValue;

        return $this;
    }

    /**
     * Get all property values attached to this complex value
     * @return PropertyValue[]|PropertyValues Property values
     */
    public function getPropertyValues(): PropertyValues
    {
        return $this->propertyValues;
    }

    /**
     * Get a property value attached to this complex value
     * @param  Property  $property  Property
     * @return Primitive|ComplexValue|null Property value
     */
    public function getPropertyValue(Property $property)
    {
        return $this->propertyValues[$property]->getValue();
    }

    /**
     * Get a property value from this complex value
     * @param  mixed  $offset  Property name
     * @return ?PropertyValue Property value
     */
    public function offsetGet($offset): ?PropertyValue
    {
        return $this->propertyValues->get($offset);
    }

    /**
     * Create a new property value on this complex value
     * @param  mixed  $offset  Property name
     * @param  mixed  $value  Property value
     */
    public function offsetSet($offset, $value): void
    {
        if (Str::startsWith($offset, '@')) {
            return;
        }

        $property = $this->getType()->getProperty($offset);
        if ($property === null) {
            $property = new DynamicProperty($offset, Type::fromInternalValue($value));
        }

        $propertyType = $property->getType();
        $propertyValue = $this->propertyValues[$property];

        if (!$propertyValue) {
            $propertyValue = $this->newPropertyValue();
            $propertyValue->setProperty($property);

            $this->addPropertyValue($propertyValue);
        }

        switch (true) {
            case $propertyType instanceof PrimitiveType:
                if ($value instanceof Primitive) {
                    $propertyValue->setValue($value);
                } else {
                    $propertyValue->setValue($propertyType->instance($value));
                }
                break;

            case $propertyType instanceof ComplexType:
                if (!$propertyValue->hasValue()) {
                    if ($value instanceof ComplexValue) {
                        $propertyValue->setValue($value);
                    } else {
                        $propertyValue->setValue($propertyType->instance($value));
                    }
                    break;
                }

                $complexValue = $propertyValue->getComplexValue();

                foreach ($value as $k => $v) {
                    $complexValue[$k] = $v;
                }
                break;
        }
    }

    /**
     * Remove a property value from this complex value
     * @param  mixed  $offset  Property name
     */
    public function offsetUnset($offset): void
    {
        $this->propertyValues->drop($offset);
    }

    /**
     * Generate a new property value attached to this complex value
     * @return PropertyValue Property value
     */
    public function newPropertyValue(): PropertyValue
    {
        $pv = new PropertyValue();
        $pv->setParent($this);
        return $pv;
    }

    /**
     * Recursively convert this complex value to a nested PHP array of key/value pairs
     * @return array Record
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->getPropertyValues() as $propertyValue) {
            $property = $propertyValue->getProperty();
            $propertyType = $property->getType();
            $propertyName = $property->getName();

            switch (true) {
                case $propertyType instanceof PrimitiveType:
                    $result[$propertyName] = $propertyValue->getPrimitive()->toMixed();
                    break;

                case $propertyType instanceof ComplexType:
                    $result[$propertyName] = $propertyValue->getComplexValue()->toArray();
                    break;
            }
        }

        return $result;
    }

    public function emitJson(Transaction $transaction): void
    {
        $transaction = $this->transaction ?: $transaction;

        /** @var GeneratedProperty $generatedProperty */
        foreach ($this->getType()->getGeneratedProperties() as $generatedProperty) {
            $generatedProperty->generatePropertyValue($this);
        }

        $complexType = $this->getType();
        $navigationRequests = $transaction->getNavigationRequests();

        /** @var NavigationProperty $navigationProperty */
        foreach ($this->getType()->getNavigationProperties() as $navigationProperty) {
            /** @var NavigationRequest $navigationRequest */
            $navigationRequest = $navigationRequests->get($navigationProperty->getName());

            if (!$navigationRequest) {
                continue;
            }

            $navigationPath = $navigationRequest->path();

            /** @var NavigationProperty $navigationProperty */
            $navigationProperty = $complexType->getNavigationProperties()->get($navigationPath);
            $navigationRequest->setNavigationProperty($navigationProperty);

            if (null === $navigationProperty) {
                throw new BadRequestException(
                    'nonexistent_expand_path',
                    sprintf(
                        'The requested expand path "%s" does not exist on this type',
                        $navigationPath
                    )
                );
            }

            if (!$navigationProperty->isExpandable()) {
                throw new BadRequestException(
                    'path_not_expandable',
                    sprintf(
                        'The requested path "%s" is not available for expansion on this type',
                        $navigationPath
                    )
                );
            }

            $navigationProperty->generatePropertyValue($transaction, $navigationRequest, $this);
        }

        foreach ($complexType->getDeclaredProperties()->filter(function (DeclaredProperty $property) {
            return !$this->propertyValues->exists($property->getName()) && $property->isNullable();
        }) as $property) {
            $this[$property->getName()] = null;
        }

        $transaction->outputJsonObjectStart();

        $metadata = $this->getMetadata($transaction);

        $requiresSeparator = false;

        if ($metadata->hasProperties()) {
            $transaction->outputJsonKV($metadata->getProperties());
            $requiresSeparator = true;
        }

        $this->propertyValues->rewind();

        while (true) {
            if ($this->usesReferences()) {
                break;
            }

            if (!$this->propertyValues->valid()) {
                break;
            }

            /** @var PropertyValue $propertyValue */
            $propertyValue = $this->propertyValues->current();

            if ($propertyValue->shouldEmit($transaction)) {
                if ($requiresSeparator) {
                    $transaction->outputJsonSeparator();
                }

                $transaction->outputJsonKey($propertyValue->getProperty()->getName());

                $value = $propertyValue->getValue();
                if (null === $value) {
                    $transaction->sendJson(null);
                } else {
                    $value->emitJson($transaction);
                }

                $requiresSeparator = true;
            }

            $propertyMetadata = $propertyValue->getMetadata($transaction);

            if ($propertyMetadata->hasProperties()) {
                if ($requiresSeparator) {
                    $transaction->outputJsonSeparator();
                }

                $transaction->outputJsonKV($propertyMetadata->getProperties());
                $requiresSeparator = true;
            }

            $this->propertyValues->next();
        }

        $transaction->outputJsonObjectEnd();
    }

    /**
     * Get the ETag for this complex value
     * @return string ETag
     */
    public function getETag(): string
    {
        return sprintf('W/"%s"', ETag::hash($this->toMixed()));
    }

    public function toMixed(): array
    {
        $record = [];

        /** @var PropertyValue $propertyValue */
        foreach ($this->propertyValues as $propertyValue) {
            $property = $propertyValue->getProperty();

            if ($property instanceof DeclaredProperty) {
                $value = $propertyValue->getValue();

                if ($value instanceof SerializeInterface) {
                    $record[$property->getName()] = $value->toMixed();
                }
            }
        }

        return $record;
    }

    /**
     * @param  Transaction  $transaction
     * @return MetadataContainer
     */
    protected function getMetadata(Transaction $transaction): MetadataContainer
    {
        $metadata = $this->metadata ?: $transaction->createMetadataContainer();
        $metadata['type'] = '#'.$this->getType()->getIdentifier();

        return $metadata;
    }

    /**
     * @return array
     */
    public function getOpenAPISchema(): array
    {
        return $this->getType()->getOpenAPISchema();
    }

    public function getContextUrl(Transaction $transaction): string
    {
        return $this->getType()->getContextUrl($transaction);
    }

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface {
        return null;
    }

    public function getResourceUrl(Transaction $transaction): string
    {
        return $this->getType()->getResourceUrl($transaction);
    }

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        if ($this->transaction) {
            $transaction = $this->transaction->replaceQueryParams($transaction);
        }

        $transaction->assertMethod(Request::METHOD_GET);

        $context = $context ?: $this;

        $this->metadata = $transaction->createMetadataContainer();
        $this->metadata['context'] = $context->getContextUrl($transaction);

        $response = $transaction->getResponse();

        return $response->setResourceCallback($this, function () use ($transaction) {
            $this->emitJson($transaction);
        });
    }
}
