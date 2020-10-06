<?php

namespace Flat3\OData;

use Countable;
use Flat3\OData\Controller\Response;
use Flat3\OData\Controller\Transaction;
use Flat3\OData\Exception\Internal\LexerException;
use Flat3\OData\Exception\Internal\PathNotHandledException;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Exception\Protocol\InternalServerErrorException;
use Flat3\OData\Exception\Protocol\NotFoundException;
use Flat3\OData\Exception\Protocol\NotImplementedException;
use Flat3\OData\Expression\Lexer;
use Flat3\OData\Helper\ObjectArray;
use Flat3\OData\Helper\Url;
use Flat3\OData\Interfaces\ArgumentInterface;
use Flat3\OData\Interfaces\ContextInterface;
use Flat3\OData\Interfaces\EmitInterface;
use Flat3\OData\Interfaces\EntityTypeInterface;
use Flat3\OData\Interfaces\NamedInterface;
use Flat3\OData\Interfaces\PipeInterface;
use Flat3\OData\Interfaces\QueryOptions\PaginationInterface;
use Flat3\OData\Interfaces\ResourceInterface;
use Flat3\OData\Interfaces\ServiceInterface;
use Flat3\OData\Traits\HasEntityType;
use Flat3\OData\Traits\HasName;
use Flat3\OData\Traits\HasTitle;
use Flat3\OData\Transaction\Option;
use Flat3\OData\Type\Property;
use Iterator;

abstract class EntitySet implements EntityTypeInterface, NamedInterface, ResourceInterface, ServiceInterface, ContextInterface, Iterator, Countable, EmitInterface, PipeInterface, ArgumentInterface
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

    /** @var Transaction $transaction */
    protected $transaction;

    /** @var PrimitiveType $key */
    protected $key;

    /** @var bool $isInstance */
    protected $isInstance = false;

    public function __construct(string $name, EntityType $entityType)
    {
        $this->setName($name);

        $this->type = $entityType;
        $this->navigationBindings = new ObjectArray();
    }

    public function __clone()
    {
        $this->isInstance = true;
    }

    public static function factory(string $name, EntityType $entityType): self
    {
        return new static($name, $entityType);
    }

    public function asInstance(Transaction $transaction): self
    {
        if ($this->isInstance) {
            throw new InternalServerErrorException(
                'cannot_clone_entity_set_instance',
                'Attempted to clone an instance of an entity set'
            );
        }

        $set = clone $this;
        $set->transaction = $transaction;
        return $set;
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
        if (!$this->isInstance) {
            throw new InternalServerErrorException(
                'not_an_entity_set_instance',
                'This function must be run on an instance of EntitySet'
            );
        }

        if (0 === $this->top) {
            return false;
        }

        if ($this->results === null) {
            $this->results = $this->generate();
            $this->topCounter = count($this->results);
        } elseif ($this->results && !current($this->results) && !$this instanceof PaginationInterface) {
            return false;
        } elseif (!current($this->results) && ($this->topCounter < $this->topLimit)) {
            $this->top = min($this->top, $this->topLimit - $this->topCounter);
            $this->skip += count($this->results);
            $this->results = $this->generate();
            $this->topCounter += count($this->results);
        }

        return !!current($this->results);
    }

    public function setMaxPageSize(int $maxPageSize): self
    {
        $this->maxPageSize = $maxPageSize;

        return $this;
    }

    public function getEntity(PrimitiveType $key): ?Entity
    {
        $this->setKey($key);
        return $this->current();
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

        $maxPageSize = $transaction->getPreference('maxpagesize');
        $top = $transaction->getTop();
        if (!$top->hasValue() && $maxPageSize) {
            $transaction->preferenceApplied('maxpagesize', $maxPageSize);
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
            $value = $lexer->type($keyProperty->getType());
        } catch (LexerException $e) {
            throw BadRequestException::factory(
                'invalid_identifier_value',
                'The type of the provided identifier value was not valid for this entity type'
            )->lexer($lexer);
        }

        $value->setProperty($keyProperty);

        $entity = $entitySet->getEntity($value);

        if (null === $entity) {
            throw new NotFoundException('not_found', 'Entity not found');
        }

        return $entity;
    }

    public function makeEntity(): Entity
    {
        $entity = new Entity();
        $entity->setEntitySet($this);
        return $entity;
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    /**
     * Generate a single page of results, using $this->top and $this->skip, loading the results as Entity objects into $this->result_set
     */
    abstract protected function generate(): array;
}