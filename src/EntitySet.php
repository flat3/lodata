<?php

namespace Flat3\OData;

use Countable;
use Flat3\OData\Controller\Response;
use Flat3\OData\Controller\Transaction;
use Flat3\OData\Exception\Internal\LexerException;
use Flat3\OData\Exception\Internal\PathNotHandledException;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Exception\Protocol\InternalServerErrorException;
use Flat3\OData\Exception\Protocol\MethodNotAllowedException;
use Flat3\OData\Exception\Protocol\NotAcceptableException;
use Flat3\OData\Exception\Protocol\NotFoundException;
use Flat3\OData\Exception\Protocol\NotImplementedException;
use Flat3\OData\Expression\Lexer;
use Flat3\OData\Helper\Constants;
use Flat3\OData\Helper\ObjectArray;
use Flat3\OData\Helper\Url;
use Flat3\OData\Interfaces\ArgumentInterface;
use Flat3\OData\Interfaces\ContextInterface;
use Flat3\OData\Interfaces\CreateInterface;
use Flat3\OData\Interfaces\DeleteInterface;
use Flat3\OData\Interfaces\EmitInterface;
use Flat3\OData\Interfaces\EntityTypeInterface;
use Flat3\OData\Interfaces\InstanceInterface;
use Flat3\OData\Interfaces\NamedInterface;
use Flat3\OData\Interfaces\PipeInterface;
use Flat3\OData\Interfaces\QueryInterface;
use Flat3\OData\Interfaces\QueryOptions\PaginationInterface;
use Flat3\OData\Interfaces\ReadInterface;
use Flat3\OData\Interfaces\ResourceInterface;
use Flat3\OData\Interfaces\ServiceInterface;
use Flat3\OData\Interfaces\UpdateInterface;
use Flat3\OData\Traits\HasEntityType;
use Flat3\OData\Traits\HasName;
use Flat3\OData\Traits\HasTitle;
use Flat3\OData\Transaction\Option;
use Flat3\OData\Type\Property;
use Illuminate\Http\Request;
use Iterator;

abstract class EntitySet implements EntityTypeInterface, NamedInterface, ResourceInterface, ServiceInterface, ContextInterface, Iterator, Countable, EmitInterface, PipeInterface, ArgumentInterface, InstanceInterface
{
    use HasName;
    use HasTitle;
    use HasEntityType;

    /** @var ObjectArray $navigationBindings Navigation bindings */
    protected $navigationBindings;

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

    /** @var PrimitiveType $key */
    protected $key;

    /** @var Transaction $transaction */
    protected $transaction;

    public function __construct(string $name, EntityType $entityType)
    {
        $this->setName($name);

        $this->type = $entityType;
        $this->navigationBindings = new ObjectArray();
    }

    public static function factory(string $name, EntityType $entityType): self
    {
        return new static($name, $entityType);
    }

    public function setKey(PrimitiveType $key): self
    {
        $this->key = $key;
        return $this;
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
        $this->ensureInstance();

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
        $transaction->configureJsonResponse();

        foreach (
            [
                $transaction->getCount(), $transaction->getFilter(), $transaction->getOrderBy(),
                $transaction->getSearch(), $transaction->getSkip(), $transaction->getTop(),
                $transaction->getExpand()
            ] as $sqo
        ) {
            /** @var Option $sqo */
            if ($sqo->hasValue() && !is_a($this, $sqo::query_interface)) {
                throw new NotImplementedException(
                    'system_query_option_not_implemented',
                    sprintf('The %s system query option is not supported by this entity set', $sqo::param)
                );
            }
        }

        // Validate $expand
        $expand = $transaction->getExpand();
        $expand->getExpansionRequests($this->getType());

        // Validate $select
        $select = $transaction->getSelect();
        $select->getSelectedProperties($this);

        // Validate $orderby
        $orderby = $transaction->getOrderBy();
        $orderby->getSortOrders($this);

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
            'context' => $this->getContextUrl(),
        ];

        $count = $transaction->getCount();
        if (true === $count->getValue()) {
            $metadata['count'] = $setCount;
        }

        $skip = $transaction->getSkip();

        if ($top->hasValue()) {
            if ($top->getValue() + ($skip->getValue() ?: 0) < $setCount) {
                $np = $transaction->getQueryParams();
                $np['$skip'] = $top->getValue() + ($skip->getValue() ?: 0);
                $metadata['nextLink'] = Url::http_build_url(
                    $this->getResourceUrl(),
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

    public function getContextUrl(): string
    {
        $url = Transaction::getContextUrl().'#'.$this->getName();
        $properties = $this->transaction->getContextUrlProperties();

        if ($properties) {
            $url .= sprintf('(%s)', join(',', $properties));
        }

        return $url;
    }

    public function getResourceUrl(): string
    {
        $url = Transaction::getResourceUrl().$this->getName();
        $properties = $this->transaction->getResourceUrlProperties();

        if ($properties) {
            $url = Url::http_build_url($url, [
                'query' => $this->transaction->getResourceUrlProperties(),
            ]);
        }

        return $url;
    }

    public static function pipe(
        Transaction $transaction,
        string $currentComponent,
        ?string $nextComponent,
        ?PipeInterface $argument
    ): ?PipeInterface {
        $data_model = Model::get();
        $lexer = new Lexer($currentComponent);
        try {
            $entitySet = $data_model->getResources()->get($lexer->odataIdentifier());
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

        $entitySet = $entitySet->asInstance($transaction);

        if ($lexer->finished()) {
            if ($nextComponent) {
                return $entitySet;
            }

            switch ($transaction->getMethod()) {
                case Request::METHOD_GET:
                    if (!$entitySet instanceof QueryInterface) {
                        throw new NotImplementedException(
                            'entityset_cannot_query',
                            'This entity set cannot be queried',
                        );
                    }

                    return $entitySet;

                case Request::METHOD_POST:
                    if (!$entitySet instanceof CreateInterface) {
                        throw new NotImplementedException(
                            'entityset_cannot_create',
                            'This entity set cannot create entities'
                        );
                    }

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

        // Test for alternative key syntax
        $alternateKey = $lexer->maybeODataIdentifier();
        if ($alternateKey) {
            if ($lexer->maybeChar('=')) {
                // Test for referenced value syntax
                if ($lexer->maybeChar('@')) {
                    $referencedKey = $lexer->odataIdentifier();
                    $referencedValue = $transaction->getParameterAlias($referencedKey);
                    $lexer = new Lexer($referencedValue);
                }

                $keyProperty = $entitySet->getType()->getProperty($alternateKey);

                if ($keyProperty instanceof Property && !$keyProperty->isKeyable()) {
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

        try {
            $key = $lexer->type($keyProperty->getType());
        } catch (LexerException $e) {
            throw BadRequestException::factory(
                'invalid_identifier_value',
                'The type of the provided identifier value was not valid for this entity type'
            )->lexer($lexer);
        }

        $key->setProperty($keyProperty);

        if ($nextComponent || $transaction->getMethod() === Request::METHOD_GET) {
            if (!$entitySet instanceof ReadInterface) {
                throw new NotImplementedException('entity_cannot_read', 'This entity set cannot read');
            }

            $entity = $entitySet->read($key);

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

                return $entitySet->update();

            case Request::METHOD_DELETE:
                if (!$entitySet instanceof DeleteInterface) {
                    throw new NotImplementedException('entityset_cannot_delete', 'This entity set cannot delete');
                }

                return $entitySet->delete();
        }

        throw new MethodNotAllowedException('invalid_method', 'An invalid method was invoked on this entity set');
    }

    public function newEntity(): Entity
    {
        $entity = new Entity();
        $entity->setEntitySet($this);
        return $entity;
    }

    public function asInstance(Transaction $transaction): self
    {
        if ($this->transaction) {
            throw new InternalServerErrorException(
                'cannot_clone_entity_set_instance',
                'Attempted to clone an instance of an entity set'
            );
        }

        $instance = clone $this;
        $instance->transaction = $transaction;
        return $instance;
    }

    public function isInstance(): bool
    {
        return !!$this->transaction;
    }

    public function ensureInstance(): void
    {
        if ($this->isInstance()) {
            return;
        }

        throw new InternalServerErrorException(
            'not_an_instance',
            'Attempted to invoke a method that can only be run on a resource instance'
        );
    }

    public function getTransaction(): Transaction
    {
        $this->ensureInstance();

        return $this->transaction;
    }
}