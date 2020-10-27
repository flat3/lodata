<?php

namespace Flat3\Lodata;

use Countable;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\LexerException;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Exception\Protocol\MethodNotAllowedException;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Helper\Gate;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Helper\Url;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\EmitInterface;
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
use Flat3\Lodata\Interfaces\EntitySet\UpdateInterface;
use Flat3\Lodata\Interfaces\EntityTypeInterface;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\Operation\ArgumentInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Interfaces\ServiceInterface;
use Flat3\Lodata\Traits\HasIdentifier;
use Flat3\Lodata\Traits\HasTitle;
use Flat3\Lodata\Traits\HasTransaction;
use Flat3\Lodata\Transaction\Option;
use Illuminate\Http\Request;
use Iterator;

abstract class EntitySet implements EntityTypeInterface, IdentifierInterface, ResourceInterface, ServiceInterface, ContextInterface, Iterator, Countable, EmitInterface, PipeInterface, ArgumentInterface
{
    use HasIdentifier;
    use HasTitle;
    use HasTransaction;

    /** @var ObjectArray $navigationBindings Navigation bindings */
    protected $navigationBindings;

    /** @var EntityType $type */
    protected $type;

    /** @var int $top Page size to return from the query */
    protected $top = PHP_INT_MAX;

    /** @var int $skip Skip value to use in the query */
    protected $skip = 0;

    /** @var int $topCounter Total number of records fetched for internal pagination */
    private $topCounter = 0;

    /** @var int Limit of number of records to evaluate from the source */
    protected $topLimit = PHP_INT_MAX;

    /** @var int $maxPageSize Maximum pagination size allowed for this entity set */
    protected $maxPageSize = 500;

    /** @var null|Entity[] $results Result set from the query */
    protected $results = null;

    /** @var PropertyValue $expansionPropertyValue */
    protected $expansionPropertyValue;

    public function __construct(string $identifier, EntityType $entityType)
    {
        $this->setIdentifier($identifier);

        $this->type = $entityType;
        if (!Lodata::getEntityType($this->type->getIdentifier())) {
            Lodata::add($entityType);
        }
        $this->navigationBindings = new ObjectArray();
    }

    public static function factory(string $name, EntityType $entityType): self
    {
        return new static($name, $entityType);
    }

    public function getKind(): string
    {
        return 'EntitySet';
    }

    /**
     * The current entity
     *
     * @return Entity
     */
    public function current(): ?Entity
    {
        if (null === $this->results && !$this->valid()) {
            return null;
        }

        return current($this->results);
    }

    /**
     * Move to the next result
     */
    public function next(): void
    {
        next($this->results);
    }

    public function key()
    {
        $entity = $this->current();

        if (!$entity) {
            return null;
        }

        return $entity->getEntityId();
    }

    public function rewind()
    {
        throw new InternalServerErrorException('no_rewind', 'Entity sets cannot be rewound');
    }

    public function count()
    {
        $this->valid();
        return $this->results ? count($this->results) : null;
    }

    /**
     * Whether there is a current entity in the result set
     * Implements internal pagination
     *
     * @return bool
     */
    public function valid(): bool
    {
        if (0 === $this->top) {
            return false;
        }

        if ($this->results === null) {
            $this->results = $this->query();
            $this->topCounter = count($this->results);
        } elseif ($this->results && !current($this->results) && !$this instanceof PaginationInterface) {
            return false;
        } elseif (!current($this->results) && ($this->topCounter < $this->topLimit)) {
            $this->top = min($this->top, $this->topLimit - $this->topCounter);
            $this->skip += count($this->results);
            $this->results = $this->query();
            $this->topCounter += count($this->results);
        }

        return !!current($this->results);
    }

    public function setMaxPageSize(int $maxPageSize): self
    {
        $this->maxPageSize = $maxPageSize;

        return $this;
    }

    public function addNavigationBinding(NavigationBinding $binding): self
    {
        $this->navigationBindings[] = $binding;

        return $this;
    }

    public function getNavigationBindings(): ObjectArray
    {
        return $this->navigationBindings;
    }

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

    public function emit(Transaction $transaction): void
    {
        $transaction = $this->transaction ?: $transaction;
        $transaction->outputJsonArrayStart();

        while ($this->valid()) {
            $entity = $this->current();
            $entity->emit($transaction);

            $this->next();

            if (!$this->valid()) {
                break;
            }

            $transaction->outputJsonSeparator();
        }

        $transaction->outputJsonArrayEnd();
    }

    public function response(Transaction $transaction): Response
    {
        $transaction = $this->transaction ?: $transaction;

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
            if ($option->hasValue() && !is_a($this, $class, true)) {
                throw new NotImplementedException(
                    'system_query_option_not_implemented',
                    sprintf('The %s system query option is not supported by this entity set', $option::param)
                );
            }
        }

        // Validate $orderby
        $orderby = $transaction->getOrderBy();
        $orderby->getSortOrders();

        $skip = $transaction->getSkip();

        $maxPageSize = $transaction->getPreferenceValue(Constants::MAX_PAGE_SIZE);
        $top = $transaction->getTop();
        if (!$top->hasValue() && $maxPageSize) {
            $transaction->preferenceApplied(Constants::MAX_PAGE_SIZE, $maxPageSize);
            $top->setValue($maxPageSize);
        }

        $this->top = $top->hasValue() && ($top->getValue() < $this->maxPageSize) ? $top->getValue() : $this->maxPageSize;

        if ($skip->hasValue()) {
            $this->skip = $skip->getValue();
        }

        if ($top->hasValue()) {
            $this->topLimit = $top->getValue();
        }

        $setCount = $this->count();

        $metadata = [
            'context' => $this->getContextUrl($transaction),
        ];

        $count = $transaction->getCount();
        if (true === $count->getValue()) {
            $metadata['count'] = $setCount;
        }

        $skip = $transaction->getSkip();

        $metadata['readLink'] = $this->getResourceUrl($transaction);

        if ($top->hasValue() && ($top->getValue() + ($skip->getValue() ?: 0) < $setCount)) {
            $np = $transaction->getQueryParams();
            $np['$skip'] = $top->getValue() + ($skip->getValue() ?: 0);
            $metadata['nextLink'] = Url::http_build_url(
                $this->getResourceUrl($transaction),
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

        $metadata = $transaction->getMetadata()->filter($metadata);

        return $transaction->getResponse()->setCallback(function () use ($transaction, $metadata) {
            $transaction->outputJsonObjectStart();

            if ($metadata) {
                $transaction->outputJsonKV($metadata);
                $transaction->outputJsonSeparator();
            }

            $transaction->outputJsonKey('value');
            $this->emit($transaction);
            $transaction->outputJsonObjectEnd();
        });
    }

    public function getContextUrl(Transaction $transaction): string
    {
        $url = $transaction->getContextUrl().'#'.$this->getName();
        $properties = $transaction->getContextUrlProperties();

        if ($properties) {
            $url .= sprintf('(%s)', join(',', $properties));
        }

        return $url;
    }

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

    public function setExpansionPropertyValue(PropertyValue $property): self
    {
        $this->expansionPropertyValue = $property;
        return $this;
    }

    public function resolveExpansionKey(): PropertyValue
    {
        /** @var NavigationProperty $navigationProperty */
        $navigationProperty = $this->expansionPropertyValue->getProperty();
        $sourceEntity = $this->expansionPropertyValue->getEntity();

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

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
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

        if (null !== $argument) {
            throw new BadRequestException(
                'no_entity_set_receiver',
                'EntitySet does not support composition from this type'
            );
        }

        $entitySet = clone $entitySet;
        $entitySet->setTransaction($transaction);

        if ($lexer->finished()) {
            if ($nextSegment || $transaction->getMethod() === Request::METHOD_GET) {
                if (!$entitySet instanceof QueryInterface) {
                    throw new NotImplementedException(
                        'entityset_cannot_query',
                        'This entity set cannot be queried',
                    );
                }

                Gate::check('entityset.query', $entitySet, $transaction);

                return $entitySet;
            }

            switch ($transaction->getMethod()) {
                case Request::METHOD_POST:
                    if (!$entitySet instanceof CreateInterface) {
                        throw new NotImplementedException(
                            'entityset_cannot_create',
                            'This entity set cannot create entities'
                        );
                    }

                    Gate::check('entityset.create', $entitySet, $transaction);

                    return $entitySet->create();
            }

            throw new MethodNotAllowedException('invalid_method', 'An invalid method was invoked on this entity set');
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

                if ($keyProperty instanceof Property && !$keyProperty->isAlternativeKey()) {
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

        if ($nextSegment || $transaction->getMethod() === Request::METHOD_GET) {
            if (!$entitySet instanceof ReadInterface) {
                throw new NotImplementedException('entity_cannot_read', 'This entity set cannot read');
            }

            $entity = $entitySet->read($keyValue);

            if (null === $entity) {
                throw new NotFoundException('not_found', 'Entity not found');
            }

            return $entity;
        }

        switch ($transaction->getMethod()) {
            case Request::METHOD_PATCH:
            case Request::METHOD_PUT:
                if (!$entitySet instanceof UpdateInterface) {
                    throw new NotImplementedException('entityset_cannot_update', 'This entity set cannot update');
                }

                return $entitySet->update($keyValue);

            case Request::METHOD_DELETE:
                if (!$entitySet instanceof DeleteInterface) {
                    throw new NotImplementedException('entityset_cannot_delete', 'This entity set cannot delete');
                }

                return $entitySet->delete($keyValue);
        }

        throw new MethodNotAllowedException('invalid_method', 'An invalid method was invoked on this entity set');
    }

    public function newEntity(): Entity
    {
        $entity = new Entity();
        $entity->setEntitySet($this);
        return $entity;
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