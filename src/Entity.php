<?php

namespace Flat3\Lodata;

use ArrayAccess;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\ETagException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Helper\Url;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\EmitInterface;
use Flat3\Lodata\Interfaces\EntityTypeInterface;
use Flat3\Lodata\Interfaces\Operation\ArgumentInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Traits\HasTransaction;
use Flat3\Lodata\Transaction\NavigationRequest;

class Entity implements ResourceInterface, EntityTypeInterface, ContextInterface, ArrayAccess, EmitInterface, PipeInterface, ArgumentInterface
{
    use HasTransaction;

    /** @var ObjectArray $properties */
    private $properties;

    /** @var EntitySet $entitySet */
    private $entitySet;

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

    public function emit(Transaction $transaction): void
    {
        $transaction = $this->transaction ?: $transaction;
        $entityType = $this->getType();
        $navigationRequests = $transaction->getNavigationRequests();

        /** @var GeneratedProperty $generatedProperty */
        foreach ($this->getType()->getGeneratedProperties() as $generatedProperty) {
            $generatedProperty->generatePropertyValue($this);
        }

        /** @var NavigationProperty $navigationProperty */
        foreach ($this->getType()->getNavigationProperties() as $navigationProperty) {
            /** @var NavigationRequest $navigationRequest */
            $navigationRequest = $navigationRequests->get($navigationProperty->getName());

            if (!$navigationRequest) {
                continue;
            }

            $navigationPath = $navigationRequest->getBasePath();

            /** @var NavigationProperty $navigationProperty */
            $navigationProperty = $entityType->getNavigationProperties()->get($navigationPath);
            $navigationRequest->setNavigationProperty($navigationProperty);

            if (null === $navigationProperty) {
                throw new BadRequestException(
                    'nonexistent_expand_path',
                    sprintf(
                        'The requested expand path "%s" does not exist on this entity type',
                        $navigationPath
                    )
                );
            }

            if (!$navigationProperty->isExpandable()) {
                throw new BadRequestException(
                    'path_not_expandable',
                    sprintf(
                        'The requested path "%s" is not available for expansion on this entity type',
                        $navigationPath
                    )
                );
            }

            $navigationProperty->generatePropertyValue($transaction, $navigationRequest, $this);
        }

        $transaction->outputJsonObjectStart();

        $metadata = $this->metadata;

        $metadata['type'] = '#'.$this->getType()->getIdentifier();

        if ($this->entitySet && $this->getEntityId()) {
            $metadata['id'] = $this->getResourceUrl($transaction);
            $metadata['readLink'] = $metadata['id'];
        }

        $metadata = $transaction->getMetadata()->filter($metadata);

        $requiresSeparator = false;

        if ($metadata) {
            $transaction->outputJsonKV($metadata);
            $requiresSeparator = true;
        }

        while (true) {
            if (!$this->properties->valid()) {
                break;
            }

            /** @var PropertyValue $propertyValue */
            $propertyValue = $this->properties->current();

            if (!$propertyValue->shouldEmit($transaction)) {
                $this->properties->next();
                continue;
            }

            if ($requiresSeparator) {
                $transaction->outputJsonSeparator();
            }

            if ($propertyValue->getProperty() instanceof NavigationProperty) {
                $propertyMetadata = $this->getExpansionMetadata($transaction, $propertyValue);

                if ($propertyMetadata) {
                    $transaction->outputJsonKV($propertyMetadata);
                    $transaction->outputJsonSeparator();
                }
            }

            $transaction->outputJsonKey($propertyValue->getProperty()->getName());

            $value = $propertyValue->getValue();
            if ($value instanceof EmitInterface) {
                $propertyValue->getValue()->emit($transaction);
            } else {
                $transaction->outputJsonValue($value);
            }

            $requiresSeparator = true;
            $this->properties->next();
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

    public function getPropertyValues(): ObjectArray
    {
        return $this->properties;
    }

    public function getPropertyValue(Property $property): ?Primitive
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

    public function getExpansionMetadata(Transaction $transaction, PropertyValue $propertyValue)
    {
        $propertyMetadata = [
            'navigationLink' => $this->getResourceUrl($transaction).'/'.$propertyValue->getProperty()->getName(),
        ];

        if ($propertyValue->getValue() instanceof EntitySet) {
            $set = $propertyValue->getEntitySetValue();
            $transaction = $set->getTransaction();

            $count = $set->count();

            if ($transaction->getCount()->hasValue()) {
                $propertyMetadata['count'] = $count;
            }

            $top = $transaction->getTop();
            $skip = $transaction->getSkip();

            if ($top->hasValue() && ($top->getValue() + ($skip->getValue() ?: 0) < $count)) {
                $np = $transaction->getQueryParams();
                $np['$skip'] = $top->getValue() + ($skip->getValue() ?: 0);
                $propertyMetadata['nextLink'] = Url::http_build_url(
                    $propertyMetadata['navigationLink'],
                    [
                        'query' => http_build_query(
                            $np,
                            null,
                            '&',
                            PHP_QUERY_RFC3986
                        ),
                    ],
                    Url::HTTP_URL_JOIN_QUERY
                );
            }
        }

        $propertyMetadata = $transaction->getMetadata()->filter(
            $propertyMetadata,
            $propertyValue->getProperty()->getName()
        );

        return $propertyMetadata;
    }

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface {
        return $argument;
    }

    public function getContextUrl(Transaction $transaction): string
    {
        if ($this->entitySet) {
            $url = $this->entitySet->getContextUrl($transaction);

            return $url.'/$entity';
        }

        $url = $this->type->getContextUrl($transaction);

        $properties = $transaction->getContextUrlProperties();

        if ($properties) {
            $url .= sprintf('(%s)', join(',', $properties));
        }

        return $url;
    }

    public function getResourceUrl(Transaction $transaction): string
    {
        if (!$this->entitySet) {
            throw new InternalServerErrorException(
                'no_entity_set',
                'Entity is only a resource as part of an entity set'
            );
        }

        if (!$this->getEntityId()) {
            throw new InternalServerErrorException(
                'no_entity_id',
                'Entity is only a resource if it has an ID',
            );
        }

        return sprintf(
            '%s(%s)',
            $this->entitySet->getResourceUrl($transaction),
            $this->getEntityId()->getPrimitiveValue()->toUrl()
        );
    }

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        if ($this->transaction) {
            $transaction = $this->transaction->replaceQueryParams($transaction);
        }

        $context = $context ?: $this;

        $this->metadata = [
            'context' => $context->getContextUrl($transaction),
        ];

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
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
