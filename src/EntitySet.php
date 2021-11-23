<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use Flat3\Lodata\Annotation\Capabilities;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\LexerException;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Exception\Protocol\MethodNotAllowedException;
use Flat3\Lodata\Exception\Protocol\NoContentException;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\Annotations;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Helper\Gate;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Helper\Properties;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Helper\PropertyValues;
use Flat3\Lodata\Helper\Url;
use Flat3\Lodata\Interfaces\AnnotationInterface;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\EntitySet\CountInterface;
use Flat3\Lodata\Interfaces\EntitySet\CreateInterface;
use Flat3\Lodata\Interfaces\EntitySet\DeleteInterface;
use Flat3\Lodata\Interfaces\EntitySet\ExpandInterface;
use Flat3\Lodata\Interfaces\EntitySet\FilterInterface;
use Flat3\Lodata\Interfaces\EntitySet\OrderByInterface;
use Flat3\Lodata\Interfaces\EntitySet\PaginationInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\EntitySet\ReadInterface;
use Flat3\Lodata\Interfaces\EntitySet\SearchInterface;
use Flat3\Lodata\Interfaces\EntitySet\TokenPaginationInterface;
use Flat3\Lodata\Interfaces\EntitySet\UpdateInterface;
use Flat3\Lodata\Interfaces\EntityTypeInterface;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\JsonInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ReferenceInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Interfaces\ResponseInterface;
use Flat3\Lodata\Interfaces\ServiceInterface;
use Flat3\Lodata\Traits\HasAnnotations;
use Flat3\Lodata\Traits\HasIdentifier;
use Flat3\Lodata\Traits\HasNavigation;
use Flat3\Lodata\Traits\HasTitle;
use Flat3\Lodata\Traits\HasTransaction;
use Flat3\Lodata\Traits\UseReferences;
use Flat3\Lodata\Transaction\MetadataContainer;
use Flat3\Lodata\Transaction\Option;
use Flat3\Lodata\Type\Stream;
use Generator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

/**
 * Entity Set
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530394
 * @package Flat3\Lodata
 */
abstract class EntitySet implements EntityTypeInterface, ReferenceInterface, IdentifierInterface, ResourceInterface, ServiceInterface, ContextInterface, JsonInterface, PipeInterface, AnnotationInterface, ResponseInterface
{
    use HasIdentifier;
    use UseReferences;
    use HasTitle;
    use HasTransaction;
    use HasAnnotations;
    use HasNavigation;

    /**
     * Entity type of this entity set
     * @var EntityType $type
     */
    protected $type;

    /**
     * Class of generated entities
     * @var Entity string
     */
    protected $entityClass = Entity::class;

    /**
     * Whether to apply system query options on this entity set instance
     * @var bool $applyQueryOptions
     */
    protected $applyQueryOptions = true;

    public function __construct(string $identifier, ?EntityType $entityType = null)
    {
        $this->setIdentifier($identifier);

        if ($entityType) {
            $this->setType($entityType);
        }

        $this->addAnnotation(
            (new Capabilities\V1\CountRestrictions())
                ->setCountable($this instanceof CountInterface)
        );

        $this->addAnnotation(
            (new Capabilities\V1\TopSupported())
                ->setSupported($this instanceof PaginationInterface || $this instanceof TokenPaginationInterface)
        );

        $this->addAnnotation(
            (new Capabilities\V1\SkipSupported())
                ->setSupported($this instanceof PaginationInterface)
        );

        $this->addAnnotation(
            (new Capabilities\V1\FilterRestrictions())
                ->setFilterable($this instanceof FilterInterface)
        );

        $this->addAnnotation(
            (new Capabilities\V1\SortRestrictions())
                ->setSortable($this instanceof OrderByInterface)
        );

        $this->addAnnotation(
            (new Capabilities\V1\ExpandRestrictions())
                ->setExpandable($this instanceof ExpandInterface)
        );

        $this->addAnnotation(
            (new Capabilities\V1\SearchRestrictions())
                ->setSearchable($this instanceof SearchInterface)
        );

        $this->addAnnotation(
            (new Capabilities\V1\InsertRestrictions())
                ->setInsertable($this instanceof CreateInterface)
        );

        $this->addAnnotation(
            (new Capabilities\V1\UpdateRestrictions())
                ->setUpdatable($this instanceof UpdateInterface)
        );

        $this->addAnnotation(
            (new Capabilities\V1\DeleteRestrictions())
                ->setDeletable($this instanceof DeleteInterface)
        );

        $this->addAnnotation(
            (new Capabilities\V1\ReadRestrictions())
                ->setReadable($this instanceof ReadInterface)
        );

        $this->addAnnotation(new Capabilities\V1\IndexableByKey());
        $this->addAnnotation(new Capabilities\V1\SelectSupport());

        $this->navigationBindings = new ObjectArray();
    }

    /**
     * Generate a new entity set definition
     * @param  string  $name  Entity set name
     * @param  EntityType  $entityType  Entity type
     * @return static Entity set definition
     * @codeCoverageIgnore
     * @deprecated
     */
    public static function factory(string $name, EntityType $entityType): self
    {
        /** @phpstan-ignore-next-line */
        return new static($name, $entityType);
    }

    /**
     * Get the OData kind of this resource
     * @return string Kind
     */
    public function getKind(): string
    {
        return 'EntitySet';
    }

    public function emitJson(Transaction $transaction): void
    {
        assert($this instanceof QueryInterface);

        $transaction = $this->transaction ?: $transaction;

        // Validate $orderby
        $orderby = $transaction->getOrderBy();
        $orderby->getSortOrders();

        $top = $transaction->getTop();

        $maxPageSize = $transaction->getPreferenceValue(Constants::maxPageSize);
        if (!$top->hasValue() && $maxPageSize) {
            $top->setValue($maxPageSize);
        }

        /** @var Generator $results */
        $results = $this->query();

        $transaction->outputJsonArrayStart();

        $limit = $top->getValue();

        while ($results->valid()) {
            if ($top->hasValue() && $limit === 0) {
                break;
            }

            $entity = $results->current();

            if ($this->usesReferences()) {
                $entity->useReferences();
            }

            $entity->emitJson($transaction);
            $this->getSkip()->increment();
            $results->next();

            if (!$results->valid() || --$limit === 0) {
                break;
            }

            $transaction->outputJsonSeparator();
        }

        $transaction->outputJsonArrayEnd();
    }

    /**
     * Read this entity set
     * @param  Transaction  $transaction  Related transaction
     * @param  ContextInterface|null  $context  Current context
     * @return Response Client response
     */
    public function get(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        Gate::query($this, $transaction)->ensure();

        $top = $this->getTop();
        $maxPageSize = $transaction->getPreferenceValue(Constants::maxPageSize);

        if (!$top->hasValue() && $maxPageSize) {
            $transaction->preferenceApplied(Constants::maxPageSize, $maxPageSize);
            $top->setValue($maxPageSize);
        }

        return $transaction->getResponse()->setResourceCallback($this, function () use ($transaction, $context) {
            $context = $context ?: $this;

            $leadingMetadata = $transaction->createMetadataContainer();
            $leadingMetadata['context'] = $context->getContextUrl($transaction);
            $leadingMetadata['readLink'] = $this->getResourceUrl($transaction);

            $transaction->outputJsonObjectStart();

            if ($leadingMetadata->hasProperties()) {
                $transaction->outputJsonKV($leadingMetadata->getProperties());
                $transaction->outputJsonSeparator();
            }

            $transaction->outputJsonKey('value');
            $this->emitJson($transaction);

            $trailingMetadata = $transaction->createMetadataContainer();
            $this->addTrailingMetadata($transaction, $trailingMetadata, $this->getResourceUrl($transaction));

            if ($trailingMetadata->hasProperties()) {
                $transaction->outputJsonSeparator();
                $transaction->outputJsonKV($trailingMetadata->getProperties());
            }

            $transaction->outputJsonObjectEnd();
        });
    }

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        if ($this->transaction) {
            $transaction = $this->transaction->replaceQueryParams($transaction);
        }

        switch ($transaction->getMethod()) {
            case Request::METHOD_GET:
                if (!$this instanceof QueryInterface) {
                    throw new NotImplementedException(
                        'entityset_cannot_query',
                        'This entity set cannot be queried',
                    );
                }

                return $this->get($transaction, $context);

            case Request::METHOD_POST:
                if (!$this instanceof CreateInterface) {
                    throw new NotImplementedException(
                        'entityset_cannot_create',
                        'This entity set cannot create entities'
                    );
                }

                if (!$this->getType()->hasKey()) {
                    throw new BadRequestException(
                        'entityset_missing_key',
                        'The type of this entity set has no key'
                    );
                }

                Gate::create($this, $transaction)->ensure();

                $transaction->assertContentTypeJson();
                $transaction->getResponse()->setStatusCode(Response::HTTP_CREATED);

                $propertyValues = $this->arrayToPropertyValues($this->getTransaction()->getBody());
                $entity = $this->create($propertyValues);
                $transaction->processDeltaPayloads($entity);

                if (
                    $transaction->getPreferenceValue(Constants::return) === Constants::minimal &&
                    !$transaction->getSelect()->hasValue() &&
                    !$transaction->getExpand()->hasValue()
                ) {
                    throw (new NoContentException)
                        ->header(Constants::preferenceApplied, Constants::return.'='.Constants::minimal)
                        ->header(Constants::odataEntityId, $entity->getResourceUrl($transaction));
                }

                $transaction->getResponse()->headers->add(['Location' => $entity->getResourceUrl($transaction)]);

                return $entity->get($transaction, $context);
        }

        throw new MethodNotAllowedException();
    }

    /**
     * Convert the provided PHP array to a set of property values for the entity type attached to this set
     * @param  array  $values  PHP values
     * @return PropertyValues Property value array
     */
    public function arrayToPropertyValues(array $values): PropertyValues
    {
        $propertyValues = new PropertyValues();

        foreach ($values as $key => $value) {
            $propertyValue = new PropertyValue();
            $property = $this->getType()->getProperty($key);

            if (Str::startsWith($key, ['$', '@'])) {
                continue;
            }

            if (!$property) {
                $property = new DynamicProperty($key, Type::fromInternalValue($value));
            }

            $propertyValue->setProperty($property);
            $propertyValue->setValue($property->getType()->instance($value));
            $propertyValues[] = $propertyValue;
        }

        return $propertyValues;
    }

    /**
     * Get the context URL for this entity set instance
     * @param  Transaction  $transaction  Related transaction
     * @return string Context URL
     */
    public function getContextUrl(Transaction $transaction): string
    {
        if ($this->usesReferences()) {
            return $transaction->getContextUrl().'#Collection($ref)';
        }

        $url = $transaction->getContextUrl().'#'.$this->getName();
        $properties = $transaction->getProjectedProperties();

        if ($properties) {
            $url .= sprintf('(%s)', join(',', $properties));
        }

        return $url;
    }

    /**
     * Get the resource URL for this entity set instance
     * @param  Transaction  $transaction  Related transaction
     * @return string Resource URL
     */
    public function getResourceUrl(Transaction $transaction): string
    {
        $url = Transaction::getResourceUrl().$this->getName();
        $properties = $transaction->getResourceUrlProperties();

        if ($properties) {
            $url = Url::http_build_url($url, [
                'query' => $properties,
            ]);
        }

        return $url;
    }

    /**
     * Set the context transaction this entity set instance should use
     * @param  Transaction  $transaction  Related transaction
     * @return $this
     */
    public function setTransaction(Transaction $transaction): self
    {
        if ($this->applyQueryOptions) {
            foreach (
                [
                    [CountInterface::class, $transaction->getCount()],
                    [FilterInterface::class, $transaction->getFilter()],
                    [OrderByInterface::class, $transaction->getOrderBy()],
                    [SearchInterface::class, $transaction->getSearch()],
                    [PaginationInterface::class, $transaction->getSkip()],
                    [[PaginationInterface::class, TokenPaginationInterface::class], $transaction->getTop()],
                    [ExpandInterface::class, $transaction->getExpand()]
                ] as $sqo
            ) {
                /** @var Option $option */
                list ($classes, $option) = $sqo;

                if (!$option->hasValue()) {
                    continue;
                }

                foreach (Arr::wrap($classes) as $class) {
                    if (is_a($this, $class, true)) {
                        continue 2;
                    }
                }

                throw new NotImplementedException(
                    'system_query_option_not_implemented',
                    sprintf('The %s system query option is not supported by this entity set', $option::param)
                );
            }
        }

        $this->transaction = $transaction;
        $transaction->attachEntitySet($this);

        return $this;
    }

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment = null,
        ?PipeInterface $argument = null
    ): ?PipeInterface {
        $lexer = new Lexer($currentSegment);

        try {
            $identifier = $lexer->qualifiedIdentifier();
        } catch (LexerException $e) {
            throw new PathNotHandledException();
        }

        $entitySet = Lodata::getEntitySet($identifier);

        if (!$entitySet instanceof EntitySet || !$entitySet->getIdentifier()->matchesNamespace($identifier)) {
            throw new PathNotHandledException();
        }

        if ($argument instanceof Entity || $argument instanceof EntitySet) {
            throw new PathNotHandledException();
        }

        $entitySet = clone $entitySet;

        if ($nextSegment && !Str::startsWith($nextSegment, '$')) {
            $entitySet->applyQueryOptions = false;
        }

        $entitySet->setTransaction($transaction);

        if ($lexer->finished()) {
            return $entitySet;
        }

        try {
            $id = $lexer->matchingParenthesis();
        } catch (LexerException $e) {
            throw new BadRequestException('invalid_entity_set_suffix', 'The expected entity set suffix was not found');
        }

        $lexer = new Lexer($id);

        // Get the default key property
        $keyProperty = $entitySet->getType()->getKey();

        if (!$keyProperty) {
            throw new InternalServerErrorException(
                'invalid_key_property',
                'The key property defined on this entity type is not valid'
            );
        }

        // Test for alternative key syntax
        $alternateKey = $lexer->maybeIdentifier();
        if ($alternateKey) {
            if ($lexer->maybeChar('=')) {
                // Test for referenced value syntax
                if ($lexer->maybeChar('@')) {
                    $referencedKey = $lexer->identifier();
                    $referencedValue = $transaction->getParameterAlias($referencedKey);
                    $lexer = new Lexer($referencedValue);
                }

                $keyProperty = $entitySet->getType()->getProperty($alternateKey);

                if ($keyProperty instanceof DeclaredProperty && !$keyProperty->isAlternativeKey()) {
                    throw new BadRequestException(
                        'property_not_alternative_key',
                        sprintf(
                            'The requested property (%s) is not configured as an alternative key',
                            $alternateKey
                        )
                    );
                }
            } else {
                // Captured value was not an alternative key, reset the lexer
                $lexer = new Lexer($id);
            }
        }

        if (null === $keyProperty) {
            throw new BadRequestException('invalid_key_property', 'The requested key property was not valid');
        }

        $keyValue = new PropertyValue();
        $keyValue->setProperty($keyProperty);

        try {
            $keyValue->setValue($lexer->type($keyProperty->getPrimitiveType()));
        } catch (LexerException $e) {
            throw (new BadRequestException(
                'invalid_identifier_value',
                'The type of the provided identifier value was not valid for this entity type'
            ))->lexer($lexer);
        }

        if (!$entitySet instanceof ReadInterface) {
            throw new NotImplementedException('entity_cannot_read', 'This entity set cannot read');
        }

        $entity = $entitySet->read($keyValue);

        if (null === $entity) {
            throw new NotFoundException('not_found', 'Entity not found');
        }

        return $entity;
    }

    /**
     * Generate a new entity attached to this entity set instance
     * @return Entity Entity
     */
    public function newEntity(): Entity
    {
        $entity = App::make($this->entityClass);
        $entity->setEntitySet($this);

        return $entity;
    }

    /**
     * Get the entity type of this entity set
     * @return EntityType Entity type
     */
    public function getType(): EntityType
    {
        return $this->type;
    }

    /**
     * Set the entity type of this entity set
     * @param  EntityType  $type  Entity type
     * @return $this
     */
    public function setType(EntityType $type): self
    {
        $this->type = $type;

        if (!Lodata::getEntityType($type->getIdentifier())) {
            Lodata::add($type);
        }

        return $this;
    }

    /**
     * Return the search option that applies to this entity set
     * @return Option\Search
     */
    public function getSearch(): Option\Search
    {
        return $this->applyQueryOptions ? $this->transaction->getSearch() : new Option\Search();
    }

    /**
     * Return the filter option that applies to this entity set
     * @return Option\Filter
     */
    public function getFilter(): Option\Filter
    {
        return $this->applyQueryOptions ? $this->transaction->getFilter() : new Option\Filter();
    }

    /**
     * Return the count option that applies to this entity set
     * @return Option\Count
     */
    public function getCount(): Option\Count
    {
        return $this->applyQueryOptions ? $this->transaction->getCount() : new Option\Count();
    }

    /**
     * Return the orderby option that applies to this entity set
     * @return Option\OrderBy
     */
    public function getOrderBy(): Option\OrderBy
    {
        return $this->applyQueryOptions ? $this->transaction->getOrderBy() : new Option\OrderBy();
    }

    /**
     * Return the skip option that applies to this entity set
     * @return Option\Skip
     */
    public function getSkip(): Option\Skip
    {
        return $this->applyQueryOptions ? $this->transaction->getSkip() : new Option\Skip();
    }

    /**
     * Return the skip token option that applies to this entity set
     * @return Option\SkipToken
     */
    public function getSkipToken(): Option\SkipToken
    {
        return $this->applyQueryOptions ? $this->transaction->getSkipToken() : new Option\SkipToken();
    }

    /**
     * Return the top option that applies to this entity set
     * @return Option\Top
     */
    public function getTop(): Option\Top
    {
        return $this->applyQueryOptions ? $this->transaction->getTop() : new Option\Top();
    }

    /**
     * Return this select option that applies to this entity set
     * @return Option\Select
     */
    public function getSelect(): Option\Select
    {
        return $this->applyQueryOptions ? $this->transaction->getSelect() : new Option\Select();
    }

    /**
     * Return this index option that applies to this entity set
     * @return Option\Index
     */
    public function getIndex(): Option\Index
    {
        return $this->applyQueryOptions ? $this->transaction->getIndex() : new Option\Index();
    }

    /**
     * Generate trailing metadata for this entity set
     * @param  Transaction  $transaction
     * @param  MetadataContainer  $metadata
     * @param  string  $resourceUrl
     */
    public function addTrailingMetadata(Transaction $transaction, MetadataContainer $metadata, string $resourceUrl)
    {
        $count = null;

        if (
            ($this instanceof CountInterface && $transaction->getCount()->hasValue()) ||
            ($this instanceof PaginationInterface && $transaction->getTop()->hasValue())
        ) {
            $count = $this->count();
        }

        if ($this instanceof CountInterface && $transaction->getCount()->hasValue()) {
            $metadata->offsetSet('count', $count);
        }

        if ($this instanceof PaginationInterface || $this instanceof TokenPaginationInterface) {
            $top = $transaction->getTop();
            $paginationParams = [];

            if ($top->hasValue()) {
                switch (true) {
                    case $this instanceof TokenPaginationInterface:
                        $skipToken = $transaction->getSkipToken();

                        if ($skipToken->hasValue()) {
                            $paginationParams[$top::param] = $top->getValue();
                            $paginationParams[$skipToken::param] = $skipToken->getValue();
                        }
                        break;

                    case $this instanceof PaginationInterface:
                        $skip = $transaction->getSkip();

                        if ($skip->hasValue() && ($count === null || $skip->getValue() < $count)) {
                            $paginationParams[$top::param] = $top->getValue();
                            $paginationParams[$skip::param] = $skip->getValue();
                        }
                        break;
                }
            }

            if ($paginationParams) {
                $transactionParams = array_diff_key(
                    $transaction->getQueryParams(),
                    array_flip([Option\Top::param, Option\Skip::param, Option\SkipToken::param]),
                    array_flip(['$'.Option\Top::param, '$'.Option\Skip::param, '$'.Option\SkipToken::param]),
                );

                $metadata['nextLink'] = Url::http_build_url(
                    $resourceUrl,
                    [
                        'query' => http_build_query(
                            array_merge($transactionParams, $paginationParams),
                            '',
                            '&',
                            PHP_QUERY_RFC3986
                        ),
                    ],
                    Url::HTTP_URL_JOIN_QUERY
                );
            }
        }
    }

    /**
     * Get selected properties
     * @return DeclaredProperty[]|Properties Properties
     */
    public function getSelectedProperties(): Properties
    {
        $select = $this->getSelect();
        $declaredProperties = $this->getType()->getDeclaredProperties();

        // Stream properties must be explicitly requested
        $declaredProperties = $declaredProperties->filter(function ($property) {
            /** @var Property $property */
            return !$property->getPrimitiveType()->is(Stream::class);
        });

        if ($select->isStar()) {
            return $declaredProperties;
        }

        if (!$select->hasValue()) {
            return $declaredProperties;
        }

        $properties = new Properties();
        $selectedProperties = $select->getCommaSeparatedValues();

        foreach ($selectedProperties as $selectedProperty) {
            $property = $this->getType()->getProperty($selectedProperty);

            if (null === $property) {
                throw new BadRequestException(
                    'property_does_not_exist',
                    sprintf(
                        'The requested property "%s" does not exist on this entity type',
                        $selectedProperty
                    )
                );
            }

            $properties[] = $property;
        }

        return $properties;
    }

    /**
     * Process a deep creation
     * @param  Transaction  $transaction
     * @return Entity
     */
    public function processDeltaCreate(Transaction $transaction): Entity
    {
        if (!$this instanceof CreateInterface) {
            throw new BadRequestException(
                'target_entity_set_cannot_create',
                'The requested entity set does not support create operations'
            );
        }

        Gate::create($this, $transaction)->ensure();

        $propertyValues = $this->arrayToPropertyValues($this->transaction->getBody());
        $entity = $this->create($propertyValues);
        $transaction->processDeltaPayloads($entity);

        return $entity;
    }

    /**
     * Process a deep modification
     * @param  Transaction  $transaction
     * @param  Entity  $entity
     * @return Entity
     */
    public function processDeltaModify(Transaction $transaction, Entity $entity): Entity
    {
        if (!$this instanceof UpdateInterface) {
            throw new BadRequestException('target_entity_set_cannot_update',
                'The requested entity set does not support update operations');
        }

        Gate::update($entity, $transaction)->ensure();

        $propertyValues = $this->arrayToPropertyValues($transaction->getBody());
        $entity = $this->update($entity->getEntityId(), $propertyValues);
        $transaction->processDeltaPayloads($entity);

        return $entity;
    }

    /**
     * Process a deep delete
     * @param  string  $reason
     * @param  Transaction  $transaction
     * @param  Entity  $entity
     */
    public function processDeltaRemove(string $reason, Transaction $transaction, Entity $entity): void
    {
        switch ($reason) {
            case 'deleted':
                if (!$this instanceof DeleteInterface) {
                    throw new BadRequestException(
                        'target_entity_set_cannot_delete',
                        'The requested entity set does not support delete operations'
                    );
                }

                Gate::delete($entity, $transaction)->ensure();
                $this->delete($entity->getEntityId());
                break;

            case 'changed':
                throw new NotImplementedException(
                    'removed_changed_not_supported',
                    'The service does not support change removals'
                );

            default:
                throw new BadRequestException(
                    'delta_removal_missing_reason',
                    'The delta payload did not include a removal reason'
                );
        }
    }

    /**
     * Get the annotations in this entity set
     * @return Annotations Annotations
     */
    public function getAnnotations(): Annotations
    {
        $annotations = $this->annotations;

        if (!$this->getType()->hasKey()) {
            $annotations->firstByClass(Capabilities\V1\ReadRestrictions::class)->setReadable(false);
            $annotations->firstByClass(Capabilities\V1\InsertRestrictions::class)->setInsertable(false);
            $annotations->firstByClass(Capabilities\V1\DeleteRestrictions::class)->setDeletable(false);
            $annotations->firstByClass(Capabilities\V1\UpdateRestrictions::class)->setUpdatable(false);
            $annotations->firstByClass(Capabilities\V1\IndexableByKey::class)->setIndexable(false);
            $annotations->firstByClass(Capabilities\V1\DeepInsertSupport::class)->setSupported(false)->setContentIDSupported(false);
        }

        return $annotations;
    }

    /**
     * Get the OData entity set name for this class
     * @param  string  $class  Class name
     * @return string OData identifier
     */
    public static function convertClassName(string $class): string
    {
        return Str::pluralStudly(class_basename($class));
    }
}