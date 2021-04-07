<?php

namespace Flat3\Lodata;

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
use Flat3\Lodata\Expression\Parser\Filter as FilterParser;
use Flat3\Lodata\Expression\Parser\Search as SearchParser;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Helper\Gate;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Helper\Url;
use Flat3\Lodata\Interfaces\AnnotationInterface;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\EntitySet\CountInterface;
use Flat3\Lodata\Interfaces\EntitySet\CreateInterface;
use Flat3\Lodata\Interfaces\EntitySet\ExpandInterface;
use Flat3\Lodata\Interfaces\EntitySet\FilterInterface;
use Flat3\Lodata\Interfaces\EntitySet\OrderByInterface;
use Flat3\Lodata\Interfaces\EntitySet\PaginationInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\EntitySet\ReadInterface;
use Flat3\Lodata\Interfaces\EntitySet\SearchInterface;
use Flat3\Lodata\Interfaces\EntityTypeInterface;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\JsonInterface;
use Flat3\Lodata\Interfaces\Operation\ArgumentInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ReferenceInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Interfaces\ServiceInterface;
use Flat3\Lodata\Traits\HasAnnotations;
use Flat3\Lodata\Traits\HasIdentifier;
use Flat3\Lodata\Traits\HasTitle;
use Flat3\Lodata\Traits\HasTransaction;
use Flat3\Lodata\Traits\UseReferences;
use Flat3\Lodata\Transaction\MetadataContainer;
use Flat3\Lodata\Transaction\Option;
use Flat3\Lodata\Type\Stream;
use Generator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Entity Set
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530394
 * @package Flat3\Lodata
 */
abstract class EntitySet implements EntityTypeInterface, ReferenceInterface, IdentifierInterface, ResourceInterface, ServiceInterface, ContextInterface, JsonInterface, PipeInterface, ArgumentInterface, AnnotationInterface
{
    use HasIdentifier;
    use UseReferences;
    use HasTitle;
    use HasTransaction;
    use HasAnnotations;

    /**
     * Navigation bindings of this entity set
     * @var ObjectArray $navigationBindings
     * @internal
     */
    protected $navigationBindings;

    /**
     * Entity type of this entity set
     * @var EntityType $type
     * @internal
     */
    protected $type;

    /**
     * The navigation property value that relates to this entity set instance
     * @var PropertyValue $navigationPropertyValue
     * @internal
     */
    protected $navigationPropertyValue;

    /**
     * Whether to apply system query options on this entity set instance
     * @var bool $applyQueryOptions
     * @internal
     */
    protected $applyQueryOptions = true;

    /**
     * Running total of emitted entities
     * @var int $emittedEntityCount Emitted entities
     */
    private $emittedEntityCount = 0;

    public function __construct(string $identifier, EntityType $entityType)
    {
        $this->setIdentifier($identifier);

        $this->type = $entityType;

        if (!Lodata::getEntityType($this->type->getIdentifier())) {
            Lodata::add($entityType);
        }

        $this->navigationBindings = new ObjectArray();
    }

    /**
     * Generate a new entity set definition
     * @param  string  $name  Entity set name
     * @param  EntityType  $entityType  Entity type
     * @return static Entity set definition
     * @codeCoverageIgnore
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

    /**
     * Add a navigation binding to this entity set
     * @param  NavigationBinding  $binding  Navigation binding
     * @return $this
     */
    public function addNavigationBinding(NavigationBinding $binding): self
    {
        $this->navigationBindings[] = $binding;

        return $this;
    }

    /**
     * Get the navigation bindings attached to this entity set
     * @return ObjectArray
     */
    public function getNavigationBindings(): ObjectArray
    {
        return $this->navigationBindings;
    }

    /**
     * Get the navigation binding for the provided navigation property on this entity set
     * @param  NavigationProperty  $property  Navigation property
     * @return NavigationBinding|null Navigation binding
     */
    public function getBindingByNavigationProperty(NavigationProperty $property): ?NavigationBinding
    {
        /** @var NavigationBinding $navigationBinding */
        foreach ($this->navigationBindings as $navigationBinding) {
            if ($navigationBinding->getPath() === $property) {
                return $navigationBinding;
            }
        }

        return null;
    }

    public function emitJson(Transaction $transaction): void
    {
        assert($this instanceof QueryInterface);

        $transaction = $this->transaction ?: $transaction;

        // Validate $orderby
        $orderby = $transaction->getOrderBy();
        $orderby->getSortOrders();

        $top = $transaction->getTop();

        $maxPageSize = $transaction->getPreferenceValue(Constants::MAX_PAGE_SIZE);
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
            $this->emittedEntityCount++;

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
        Gate::check(Gate::QUERY, $this, $transaction);

        $top = $this->getTop();
        $maxPageSize = $transaction->getPreferenceValue(Constants::MAX_PAGE_SIZE);

        if (!$top->hasValue() && $maxPageSize) {
            $transaction->preferenceApplied(Constants::MAX_PAGE_SIZE, $maxPageSize);
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

                Gate::check(Gate::CREATE, $this, $transaction);

                $transaction->assertContentTypeJson();
                $transaction->getResponse()->setStatusCode(Response::HTTP_CREATED);

                $result = $this->create();

                if (
                    $transaction->getPreferenceValue(Constants::RETURN) === Constants::MINIMAL &&
                    !$transaction->getSelect()->hasValue() &&
                    !$transaction->getExpand()->hasValue()
                ) {
                    throw NoContentException::factory()
                        ->header(Constants::PREFERENCE_APPLIED, Constants::RETURN.'='.Constants::MINIMAL)
                        ->header(Constants::ODATA_ENTITY_ID, $result->getResourceUrl($transaction));
                }

                $transaction->getResponse()->headers->add(['Location' => $result->getResourceUrl($transaction)]);

                return $result->get($transaction, $context);
        }

        throw new MethodNotAllowedException();
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
        $properties = $transaction->getContextUrlProperties();

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
     * Get the navigation property value that relates to this entity set instance
     * @param  PropertyValue  $property  Navigation property value
     * @return $this
     */
    public function setNavigationPropertyValue(PropertyValue $property): self
    {
        $this->navigationPropertyValue = $property;
        return $this;
    }

    /**
     * Get the entity ID of the entity this set was generated from using the attached expansion property value
     * @return PropertyValue Entity ID
     */
    public function resolveExpansionKey(): PropertyValue
    {
        /** @var NavigationProperty $navigationProperty */
        $navigationProperty = $this->navigationPropertyValue->getProperty();
        $sourceEntity = $this->navigationPropertyValue->getEntity();

        $targetConstraint = null;
        /** @var ReferentialConstraint $constraint */
        foreach ($navigationProperty->getConstraints() as $constraint) {
            if ($this->getType()->getProperty($constraint->getReferencedProperty()) && $sourceEntity->getEntitySet()->getType()->getProperty($constraint->getProperty())) {
                $targetConstraint = $constraint;
                break;
            }
        }

        if (!$targetConstraint) {
            throw new BadRequestException(
                'no_expansion_constraint',
                sprintf(
                    'No applicable constraint could be found between sets %s and %s for expansion',
                    $sourceEntity->getEntitySet()->getIdentifier(),
                    $this->getIdentifier()
                )
            );
        }

        /** @var PropertyValue $keyPropertyValue */
        $keyPropertyValue = $sourceEntity->getPropertyValues()->get($targetConstraint->getProperty());
        if ($keyPropertyValue->getPrimitiveValue()->get() === null) {
            throw new InternalServerErrorException('missing_expansion_key', 'The target constraint key is null');
        }

        $referencedProperty = $targetConstraint->getReferencedProperty();

        $targetKey = new PropertyValue();
        $targetKey->setProperty($referencedProperty);
        $targetKey->setValue($keyPropertyValue->getValue());

        return $targetKey;
    }

    /**
     * Set the context transaction this entity set instance should use
     * @param  Transaction  $transaction  Related transaction
     * @return $this
     */
    public function setTransaction(Transaction $transaction): self
    {
        foreach (
            [
                [CountInterface::class, $transaction->getCount()],
                [FilterInterface::class, $transaction->getFilter()],
                [OrderByInterface::class, $transaction->getOrderBy()],
                [SearchInterface::class, $transaction->getSearch()],
                [PaginationInterface::class, $transaction->getSkip()],
                [PaginationInterface::class, $transaction->getTop()],
                [ExpandInterface::class, $transaction->getExpand()]
            ] as $sqo
        ) {
            list ($class, $option) = $sqo;

            /** @var Option $option */
            if ($this->applyQueryOptions && $option->hasValue() && !is_a($this, $class, true)) {
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
            $entitySet = Lodata::getEntitySet($lexer->qualifiedIdentifier());
        } catch (LexerException $e) {
            throw new PathNotHandledException();
        }

        if (!$entitySet instanceof EntitySet) {
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
            throw new InternalServerErrorException('invalid_key_property',
                'The key property defined on this entity type is not valid');
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
            throw BadRequestException::factory(
                'invalid_identifier_value',
                'The type of the provided identifier value was not valid for this entity type'
            )->lexer($lexer);
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
        $entity = new Entity();
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
     * Generate trailing metadata for this entity set
     * @param  Transaction  $transaction
     * @param  MetadataContainer  $metadata
     * @param  string  $resourceUrl
     */
    public function addTrailingMetadata(Transaction $transaction, MetadataContainer $metadata, string $resourceUrl)
    {
        $count = null;

        if ($this instanceof CountInterface) {
            $count = $this->count();

            if ($transaction->getCount()->hasValue()) {
                $metadata['count'] = $count;
            }
        }

        $top = $transaction->getTop();
        $skip = $transaction->getSkip();
        $skipToken = $transaction->getSkipToken();

        if ($skipToken->isPaginationComplete()) {
            return;
        }

        $transactionParams = array_diff_key(
            $transaction->getQueryParams(),
            array_flip(['$top', '$skip', '$skiptoken'])
        );

        $paginationParams = [];

        if ($top->hasValue()) {
            if ($skipToken->hasValue()) {
                $paginationParams['$top'] = $top->getValue();
                $paginationParams['$skiptoken'] = $skipToken->getValue();
            } else {
                if ($top->hasValue()) {
                    $nextSkipValue = $this->emittedEntityCount + $skip->getValue();
                    if ($count === null || $nextSkipValue < $count) {
                        $paginationParams['$top'] = $top->getValue();
                        $paginationParams['$skip'] = $nextSkipValue;
                    }
                }
            }
        }

        if (!$paginationParams) {
            return;
        }

        $metadata['nextLink'] = Url::http_build_url(
            $resourceUrl,
            [
                'query' => http_build_query(
                    array_merge($transactionParams, $paginationParams),
                    null,
                    '&',
                    PHP_QUERY_RFC3986
                ),
            ],
            Url::HTTP_URL_JOIN_QUERY
        );
    }

    /**
     * Get selected properties
     * @return DeclaredProperty[]|ObjectArray Properties
     */
    public function getSelectedProperties(): ObjectArray
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

        $properties = new ObjectArray();
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
     * Apply the filter system query option
     * @param  array  $validLiterals  List of valid literals for this entity set
     */
    public function applyFilterQueryOption(array $validLiterals = []): void
    {
        $filter = $this->getFilter();

        if (!$filter->hasValue()) {
            return;
        }

        $parser = new FilterParser($this, $this->getTransaction());

        foreach ($validLiterals as $validLiteral) {
            $parser->addValidLiteral($validLiteral);
        }

        $tree = $parser->generateTree($filter->getValue());
        $tree->compute();
    }

    /**
     * Apply the search system query option
     */
    public function applySearchQueryOption(): void
    {
        $search = $this->getSearch();

        if (!$search->hasValue()) {
            return;
        }

        $parser = new SearchParser($this);

        $tree = $parser->generateTree($search->getValue());
        $tree->compute();
    }
}