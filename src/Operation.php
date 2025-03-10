<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use Flat3\Lodata\Attributes\LodataNamespace;
use Flat3\Lodata\Attributes\LodataOperation;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\LexerException;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\ConfigurationException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Exception\Protocol\NoContentException;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\Arguments;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Helper\Discovery;
use Flat3\Lodata\Helper\Gate;
use Flat3\Lodata\Helper\JSON;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\AnnotationInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\RepositoryInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Interfaces\ServiceInterface;
use Flat3\Lodata\Operation\Argument;
use Flat3\Lodata\Operation\CollectionArgument;
use Flat3\Lodata\Operation\EntityArgument;
use Flat3\Lodata\Operation\EntityFunction;
use Flat3\Lodata\Operation\EntitySetArgument;
use Flat3\Lodata\Operation\PrimitiveArgument;
use Flat3\Lodata\Operation\Repository;
use Flat3\Lodata\Operation\TransactionArgument;
use Flat3\Lodata\Operation\ValueArgument;
use Flat3\Lodata\PathSegment\Each;
use Flat3\Lodata\Traits\HasAnnotations;
use Flat3\Lodata\Traits\HasIdentifier;
use Flat3\Lodata\Traits\HasTitle;
use Flat3\Lodata\Traits\HasTransaction;
use Flat3\Lodata\Type\Collection;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as ICollection;
use Illuminate\Support\Facades\App;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use TypeError;

/**
 * Operation
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530382
 * @package Flat3\Lodata
 */
class Operation implements ServiceInterface, ResourceInterface, IdentifierInterface, PipeInterface, AnnotationInterface
{
    use HasIdentifier;
    use HasTitle;
    use HasTransaction;
    use HasAnnotations;

    /** @var callable $callable */
    protected $callable;

    /**
     * The name of the binding parameter used in the invocation function
     * @var string $bindingParameterName
     */
    protected $bindingParameterName;

    /**
     * The OData return type from this operation
     * @var Type $returnType
     */
    protected $returnType;

    /**
     * The OData kind of this operation
     * @var string Kind
     */
    protected $kind = self::function;

    /**
     * OData operation types
     */
    const function = 'Function';
    const action = 'Action';

    /**
     * The instance of the bound parameter provided to this operation instance
     * @var ?PipeInterface $boundParameter
     */
    private $boundParameter;

    /**
     * The parameters provided by the client for this operation instance
     * @var string $clientParameters
     */
    private $clientParameters = null;

    public function __construct($identifier)
    {
        $this->setIdentifier($identifier);
    }

    /**
     * Get the OData kind of this operation
     * @return string Kind
     */
    public function getKind(): string
    {
        return $this->kind;
    }

    /**
     * Set the OData kind of this operation
     * @param  string  $kind  Kind
     * @return $this
     */
    public function setKind(string $kind): self
    {
        if (!in_array($kind, [self::function, self::action])) {
            throw new ConfigurationException(
                'invalid_operation_type',
                'An operation must be type Function or Action'
            );
        }

        $this->kind = $kind;

        return $this;
    }

    /**
     * Return whether this operation is a function
     * @return bool
     */
    public function isFunction(): bool
    {
        return $this->kind == self::function;
    }

    /**
     * Return whether this operation as an action
     * @return bool
     */
    public function isAction(): bool
    {
        return $this->kind === self::action;
    }

    /**
     * Return the attached callable
     * @return callable
     */
    public function getCallable()
    {
        $callable = $this->callable;

        if (is_callable($callable)) {
            return $callable;
        }

        if (is_array($callable)) {
            list($instance, $method) = $callable;

            if (is_string($instance) && class_exists($instance)) {
                $instance = App::make($instance);
            }

            return [$instance, $method];
        }

        return $callable;
    }

    /**
     * Set the operation callable
     * @param  callable|array  $callable
     * @return $this
     */
    public function setCallable($callable): self
    {
        $this->callable = $callable;

        return $this;
    }

    /**
     * Get the method parameter name of the binding parameter used on the invocation method
     * @return string|null Binding parameter name
     */
    public function getBindingParameterName(): ?string
    {
        return $this->bindingParameterName;
    }

    /**
     * Set the name of the invocation method parameter used to receive the binding parameter
     * @param  string  $bindingParameterName  Binding parameter name
     * @return $this
     */
    public function setBindingParameterName(string $bindingParameterName): self
    {
        $this->bindingParameterName = $bindingParameterName;

        return $this;
    }

    /**
     * Return whether this operation expects a bound parameter
     * @return bool
     */
    public function isBound(): bool
    {
        return $this->bindingParameterName !== null;
    }

    /**
     * Whether the result of this operation is a collection
     * @return bool
     */
    public function returnsCollection(): bool
    {
        $returnType = $this->getCallableReturnType();

        return $returnType === 'array' ||
            is_a($returnType, Collection::class, true) ||
            is_a($returnType, EntitySet::class, true);
    }

    /**
     * Whether the result of this operation can be null
     * @return bool
     */
    public function isNullable(): bool
    {
        $rfn = $this->getCallableMethod();

        return !$rfn->hasReturnType() || $rfn->getReturnType()->allowsNull() || $rfn->getReturnType()->getName() === 'void';
    }

    /**
     * Get the OData return type of this operation
     * @return Type|null Return type
     */
    public function getReturnType(): ?Type
    {
        if ($this->returnType) {
            return $this->returnType;
        }

        $returnType = $this->getCallableReturnType();

        if (is_a($returnType, Primitive::class, true)) {
            return new PrimitiveType($returnType);
        }

        if (is_a($returnType, ComplexValue::class, true)) {
            return null;
        }

        return Type::fromInternalType($returnType);
    }

    /**
     * Set the OData type that will be returned by this operation
     * @param  Type  $type  Return type
     * @return $this
     */
    public function setReturnType(Type $type): self
    {
        $this->returnType = $type;

        return $this;
    }

    /**
     * Retrieve the bound parameter attached to this operation
     * @return PipeInterface|null
     */
    public function getBoundParameter(): ?PipeInterface
    {
        return $this->boundParameter;
    }

    /**
     * Set the bound parameter on an instance of this operation
     * @param  mixed  $parameter  Binding parameter
     * @return $this
     */
    public function setBoundParameter($parameter): self
    {
        $this->assertTransaction();

        if ($parameter instanceof PropertyValue) {
            $parameter = $parameter->getValue();
        }

        $this->boundParameter = $parameter;

        return $this;
    }

    public function setClientParameters(?string $parameters): self
    {
        $this->assertTransaction();

        $this->clientParameters = $parameters;

        return $this;
    }

    /**
     * Get the resource URL of this operation instance
     * @param  Transaction  $transaction  Related transaction
     * @return string Resource URL
     */
    public function getResourceUrl(Transaction $transaction): string
    {
        return $transaction->getResourceUrl().$this->getName();
    }

    /**
     * Get the reflected function or method attached to this operation
     * @return ReflectionFunction|ReflectionMethod
     */
    public function getCallableMethod(): ReflectionFunctionAbstract
    {
        $callable = $this->getCallable();

        if (!$callable) {
            throw new ConfigurationException(
                'missing_callable',
                'The operation has no callable',
            );
        }

        if (is_array($callable)) {
            list($instance, $method) = $callable;
            return new ReflectionMethod($instance, $method);
        }

        return new ReflectionFunction($callable);
    }

    /**
     * Get the return type of this operation, based on reflection of the invocation method
     * @return string Return type
     */
    public function getCallableReturnType(): string
    {
        $callableMethod = $this->getCallableMethod();

        /** @var ReflectionNamedType $returnType */
        $returnType = $callableMethod->getReturnType();

        if (null === $returnType) {
            return 'void';
        }

        return $returnType->getName();
    }

    /**
     * Extract operation arguments for metadata
     * Ensure the binding parameter is first, if it exists. Filter out non-odata arguments.
     * @return Arguments|Argument[]
     */
    public function getMetadataArguments()
    {
        return $this->getCallableArguments()->sort(function (Argument $a, Argument $b) {
            if ($a->getName() === $this->getBindingParameterName()) {
                return -1;
            }

            if ($b->getName() === $this->getBindingParameterName()) {
                return 1;
            }

            return 0;
        })->filter(function ($argument) {
            if ($argument instanceof PrimitiveArgument || $argument instanceof ValueArgument || $argument instanceof CollectionArgument) {
                return true;
            }

            if (($argument instanceof EntitySetArgument || $argument instanceof EntityArgument) && $this->getBindingParameterName() === $argument->getName()) {
                return true;
            }

            return false;
        });
    }

    /**
     * Get the reflected arguments of the invocation of this operation
     * @return Argument[]|Arguments Arguments
     */
    public function getCallableArguments(): Arguments
    {
        $reflectionMethod = $this->getCallableMethod();
        $arguments = new Arguments();

        foreach ($reflectionMethod->getParameters() as $parameter) {
            $type = $parameter->getType()->getName();

            switch (true) {
                case is_a($type, EntitySet::class, true):
                    $arguments[] = new EntitySetArgument($this, $parameter);
                    break;

                case is_a($type, Transaction::class, true):
                    $arguments[] = new TransactionArgument($this, $parameter);
                    break;

                case is_a($type, Entity::class, true):
                    $arguments[] = new EntityArgument($this, $parameter);
                    break;

                case $type === 'array' || is_a($type, Collection::class, true):
                    $arguments[] = new CollectionArgument($this, $parameter);
                    break;

                case is_a($type, Primitive::class, true):
                    $arguments[] = new PrimitiveArgument($this, $parameter);
                    break;

                default:
                    $arguments[] = new ValueArgument($this, $parameter);
                    break;
            }
        }

        return $arguments;
    }

    /**
     * Parse and return arguments from the client that can be passed to the callable
     * @return array Arguments
     */
    public function resolveParameters(): array
    {
        if ($this->isFunction()) {
            $clientParameters = $this->parseFunctionParameters();
        }

        if ($this->isAction()) {
            $clientParameters = $this->parseActionParameters();
        }

        if ($this->isBound()) {
            $clientParameters[$this->getBindingParameterName()] = $this->boundParameter;
        }

        $callableParameters = [];

        foreach ($this->getCallableArguments() as $argument) {
            $argumentName = $argument->getName();
            $clientParameter = $clientParameters[$argumentName] ?? null;
            $callableParameters[$argumentName] = $this->resolveParameter($argument, $clientParameter);
        }

        if ($this->isBound() && ($callableParameters[$this->getBindingParameterName()] ?? null) === null) {
            throw new ConfigurationException(
                'missing_callable_binding_parameter',
                'The provided callable did not have a argument named '.$this->getBindingParameterName()
            );
        }

        return array_values($callableParameters);
    }

    /**
     * Resolve a single parameter into the correct type for the callable
     * @param  Argument  $argument  Internal argument
     * @param  mixed  $parameter  Client-provided parameter
     * @return mixed Callable parameter
     */
    public function resolveParameter(Argument $argument, $parameter)
    {
        if ($alias = $this->transaction->getImplicitParameterAlias($argument->getName())) {
            $parameter = $argument->getType()->instance($alias);
        }

        try {
            $result = $argument->resolveParameter($parameter);
        } catch (TypeError $e) {
            throw new BadRequestException('invalid_argument_type', sprintf(
                'The provided argument (%s) was not of the correct type for this function',
                $argument->getName()
            ), $e);
        }

        if (null === $result && !$argument->isNullable()) {
            throw new BadRequestException(
                'non_null_argument_missing',
                sprintf('A non-null argument (%s) is missing', $argument->getName())
            );
        }

        return $result;
    }

    /**
     * Parse the arguments provided to this function
     * @return ICollection
     */
    protected function parseFunctionParameters(): ICollection
    {
        $arguments = collect();

        if (!$this->clientParameters) {
            return $arguments;
        }

        $lexer = new Lexer($this->clientParameters);
        $callableArguments = $this->getCallableArguments();

        while (!$lexer->finished()) {
            $key = $lexer->identifier();
            $lexer->char('=');

            $argument = $callableArguments[$key];

            if (!$argument) {
                throw new BadRequestException(
                    'invalid_argument',
                    'The parameters provided an argument that was not known'
                );
            }

            /** @var PrimitiveType $type */
            $type = $argument->getType();

            if ($lexer->maybeChar('@')) {
                $parameterAlias = $lexer->identifier();
                $parameterBody = $this->transaction->getParameterAlias($parameterAlias);

                if ($argument instanceof CollectionArgument) {
                    $parameterBody = JSON::decode($parameterBody);
                }

                $arguments[$key] = $type->instance($parameterBody);
            } else {
                /** @var Primitive $factory */
                $factory = $type->getFactory();

                try {
                    $result = $factory::fromLexer($lexer);
                } catch (LexerException $e) {
                    throw new BadRequestException('invalid_argument_type', sprintf(
                        'The provided argument %s was not of type %s',
                        $key,
                        $type->getIdentifier()
                    ), $e);
                }

                $arguments[$key] = $result;
            }

            $lexer->maybeChar(',');
        }

        return $arguments;
    }

    /**
     * Parse the arguments provided to this action
     * @return ICollection
     */
    protected function parseActionParameters(): ICollection
    {
        $callableArguments = $this->getCallableArguments();
        $arguments = collect();

        $body = $this->transaction->getBody();

        if (!$body) {
            return $arguments;
        }

        $body = $this->transaction->getBodyAsArray();

        foreach ($body as $key => $value) {
            $argument = $callableArguments[$key];

            if (!$argument) {
                throw new BadRequestException(
                    'invalid_argument',
                    'The action body provided an argument that was not known'
                );
            }

            $arguments[$key] = $argument->getType()->instance($value);
        }

        return $arguments;
    }

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface {
        $lexer = new Lexer($currentSegment);

        try {
            $identifier = $lexer->qualifiedIdentifier();
        } catch (LexerException $e) {
            throw new PathNotHandledException();
        }

        $operation = Lodata::getOperation($identifier);

        if (!$operation instanceof Operation || !$operation->getIdentifier()->matchesNamespace($identifier)) {
            throw new PathNotHandledException();
        }

        if ($nextSegment && $operation->isAction()) {
            throw new BadRequestException(
                'cannot_compose_action',
                'It is not permitted to further compose the result of an action'
            );
        }

        if (!$argument && $operation->isBound()) {
            throw new BadRequestException(
                'missing_bound_argument',
                'This operation is bound, but no argument was provided'
            );
        }

        if ($argument instanceof Each) {
            $entitySet = $argument->getArgument();

            if (!$entitySet instanceof QueryInterface) {
                throw new NotImplementedException(
                    'entityset_cannot_query',
                    'This entity set cannot be queried',
                );
            }

            $result = new Collection();
            $result->setUnderlyingType($operation->getReturnType());

            foreach ($entitySet->query() as $entity) {
                $operationInstance = clone $operation;
                $operationTransaction = new Transaction();
                $request = Request::create(
                    '',
                    Request::METHOD_POST,
                    [],
                    [],
                    [],
                    [],
                    $transaction->getRequest()->getContent()
                );
                $request->headers->replace($transaction->getRequestHeaders());
                $operationTransaction->initialize(new Controller\Request($request));
                $operationInstance->setTransaction($operationTransaction);
                $operationInstance->setBoundParameter($entity);
                $result[] = $operationInstance->executeAction();
            }

            return $result;
        }

        $operation = clone $operation;
        $operation->setTransaction($transaction);
        $operation->setBoundParameter($argument);
        if (!$lexer->finished()) {
            try {
                $operation->setClientParameters($lexer->matchingParenthesis());
            } catch (LexerException $e) {
                throw new BadRequestException(
                    'malformed_parameters',
                    'The provided parameters were not well formed',
                    $e
                );
            }
        }

        return $operation->isFunction() ? $operation->executeFunction() : $operation->executeAction();
    }

    /**
     * Execute this operation as a function
     * @return PipeInterface|null
     */
    public function executeFunction(): ?PipeInterface
    {
        $this->transaction->assertMethod(
            Request::METHOD_GET,
            'This operation must be addressed with a GET request'
        );

        Gate::execute($this, $this->transaction)->ensure();

        $result = $this->invoke($this->getCallable(), $this->resolveParameters());

        if ($result === null) {
            throw new InternalServerErrorException(
                'missing_function_result',
                'Function is required to return a result'
            );
        }

        $this->transaction->getRequest()->setMethod(Request::METHOD_GET);

        return $this->ensureResult($result);
    }

    /**
     * Execute this operation as an action
     * @return PipeInterface|null
     */
    public function executeAction(): ?PipeInterface
    {
        $this->transaction->assertMethod(
            Request::METHOD_POST,
            'This operation must be addressed with a POST request'
        );

        if ($this->transaction->getBody()) {
            $this->transaction->assertContentTypeJson();
        }

        Gate::execute($this, $this->transaction)->ensure();

        $result = $this->invoke($this->getCallable(), $this->resolveParameters());

        $returnPreference = $this->transaction->getPreferenceValue(Constants::return);

        if ($returnPreference === Constants::minimal) {
            throw (new NoContentException)
                ->header(Constants::preferenceApplied, Constants::return.'='.Constants::minimal);
        }

        $this->transaction->getRequest()->setMethod(Request::METHOD_GET);

        return $this->ensureResult($result);
    }

    /**
     * Invoke the provided callable
     * @return mixed
     */
    public function invoke(callable $callable, array $arguments)
    {
        return call_user_func_array($callable, $arguments);
    }

    /**
     * Ensure the returned operation result is properly formed
     * @param $result
     * @return PipeInterface|null
     */
    public function ensureResult($result): ?PipeInterface
    {
        $returnType = $this->getReturnType();

        if ($result === null && !$this->isNullable()) {
            throw new InternalServerErrorException(
                'invalid_null_returned',
                'The operation returned null but the result is not nullable'
            );
        }

        if ($returnType instanceof EntityType) {
            if (
                ($result instanceof Collection && !$result->getUnderlyingType() instanceof $returnType) ||
                ($result instanceof ComplexValue && !$result->getType() instanceof $returnType)
            ) {
                throw new InternalServerErrorException(
                    'invalid_entity_type_returned',
                    'The operation returned an entity type that did not match its defined type',
                );
            }
        }

        if ($returnType instanceof PrimitiveType && !$result instanceof Primitive) {
            return $returnType->instance($result);
        }

        return $result;
    }

    /**
     * Discover operations on the provided class and add them to the model
     * @param  string|object|EloquentModel|RepositoryInterface  $discoverable
     * @return void
     * @throws ReflectionException
     */
    public static function discover($discoverable)
    {
        if (!Discovery::supportsAttributes()) {
            return;
        }

        $namespace = null;
        $reflectionClass = new ReflectionClass($discoverable);

        /** @var ReflectionAttribute $namespaceAttribute */
        $namespaceAttribute = Arr::first($reflectionClass->getAttributes(LodataNamespace::class));
        if ($namespaceAttribute) {
            $namespace = $namespaceAttribute->newInstance()->getName();
        }

        foreach (Discovery::getReflectedMethods($discoverable) as $reflectionMethod) {
            foreach ($reflectionMethod->getAttributes(
                LodataOperation::class,
                ReflectionAttribute::IS_INSTANCEOF
            ) as $operationAttribute) {
                /** @var LodataOperation $attributeInstance */
                $attributeInstance = $operationAttribute->newInstance();

                $operationName = $attributeInstance->getName() ?: $reflectionMethod->getName();

                switch (true) {
                    case is_a($discoverable, EloquentModel::class, true):
                        $operation = new EntityFunction($operationName);
                        break;

                    case is_a($discoverable, RepositoryInterface::class, true):
                        $operation = new Repository($operationName);
                        break;

                    default:
                        $operation = new Operation($operationName);
                        break;
                }

                $operation->setKind($attributeInstance::operationType);
                $operation->setCallable([$discoverable, $reflectionMethod->getName()]);

                if ($namespace) {
                    $operation->getIdentifier()->setNamespace($namespace);
                }

                if ($attributeInstance->hasBindingParameterName()) {
                    $operation->setBindingParameterName($attributeInstance->getBindingParameterName());
                }

                if ($attributeInstance->hasReturnType()) {
                    $operation->setReturnType($attributeInstance->getReturnType());
                }

                Lodata::add($operation);
            }
        }
    }
}
