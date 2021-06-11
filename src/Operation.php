<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\LexerException;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Exception\Protocol\NoContentException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Helper\Gate;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\AnnotationInterface;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\Operation\ActionInterface;
use Flat3\Lodata\Interfaces\Operation\FunctionInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Interfaces\ServiceInterface;
use Flat3\Lodata\Operation\Argument;
use Flat3\Lodata\Operation\EntityArgument;
use Flat3\Lodata\Operation\EntitySetArgument;
use Flat3\Lodata\Operation\PrimitiveArgument;
use Flat3\Lodata\Operation\TransactionArgument;
use Flat3\Lodata\Traits\HasAnnotations;
use Flat3\Lodata\Traits\HasIdentifier;
use Flat3\Lodata\Traits\HasTitle;
use Flat3\Lodata\Traits\HasTransaction;
use Flat3\Lodata\Type\Double;
use Flat3\Lodata\Type\Int64;
use Flat3\Lodata\Type\String_;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Operation
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530382
 * @package Flat3\Lodata
 */
abstract class Operation implements ServiceInterface, ResourceInterface, IdentifierInterface, PipeInterface, AnnotationInterface
{
    use HasIdentifier;
    use HasTitle;
    use HasTransaction;
    use HasAnnotations;

    /**
     * The name of the binding parameter used in the invocation function
     * @var string $bindingParameterName
     * @internal
     */
    protected $bindingParameterName;

    /**
     * The instance of the bound parameter provided to the instance of the operation
     * @var ?PipeInterface $boundParameter
     * @internal
     */
    protected $boundParameter;

    /**
     * The URL inline parameters being provided to this operation
     * @var array $inlineParameters
     * @internal
     */
    protected $inlineParameters = [];

    /**
     * The OData return type from this operation
     * @var Type $returnType
     * @internal
     */
    protected $returnType;

    public function __construct($identifier)
    {
        if (!$this instanceof FunctionInterface && !$this instanceof ActionInterface) {
            throw new InternalServerErrorException(
                sprintf('An operation must implement either %s or %s', FunctionInterface::class, ActionInterface::class)
            );
        }

        try {
            new ReflectionMethod($this, 'invoke');
        } catch (ReflectionException $e) {
            throw new InternalServerErrorException('An operation must implement the invoke method');
        }

        $this->setIdentifier($identifier);
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

        $rrt = $this->getReflectedReturnType();

        if (is_a($rrt, Primitive::class, true)) {
            return new PrimitiveType($this->getReflectedReturnType());
        }

        switch ($rrt) {
            case 'string':
                return new PrimitiveType(String_::class);

            case 'float':
                return new PrimitiveType(Double::class);

            case 'int':
                return new PrimitiveType(Int64::class);
        }

        return null;
    }

    /**
     * Get the OData return type of this operation, based on reflection of the invocation method
     * @return string Return type
     */
    public function getReflectedReturnType(): string
    {
        try {
            $rfc = new ReflectionMethod($this, 'invoke');

            /** @var ReflectionNamedType $rt */
            $rt = $rfc->getReturnType();

            if ('void' === $rt && $this instanceof FunctionInterface) {
                throw new InternalServerErrorException('missing_return_type', 'Functions must have a return type');
            }

            if (null === $rt) {
                return 'void';
            }

            return $rt->getName();
        } catch (ReflectionException $e) {
        }

        throw new InternalServerErrorException('invalid_return_type', 'Invalid return type');
    }

    /**
     * Whether the result of this operation is a collection
     * @return bool
     */
    public function returnsCollection(): bool
    {
        $returnType = $this->getReflectedReturnType();

        switch (true) {
            case $returnType === 'array':
            case is_a($returnType, EntitySet::class, true);
                return true;
        }

        return false;
    }

    /**
     * Whether the result of this operation can be null
     * @return bool
     */
    public function isNullable(): bool
    {
        try {
            $rfn = new ReflectionMethod($this, 'invoke');
            return !$rfn->hasReturnType() || $rfn->getReturnType()->allowsNull() || $rfn->getReturnType()->getName() === 'void';
        } catch (ReflectionException $e) {
            return false;
        }
    }

    /**
     * Get the reflected arguments of the invocation of this operation
     * @return Argument[]|ObjectArray Arguments
     */
    public function getArguments(): ObjectArray
    {
        try {
            $rfn = new ReflectionMethod($this, 'invoke');
            $args = new ObjectArray();

            foreach ($rfn->getParameters() as $parameter) {
                $args[] = Argument::factory($parameter);
            }

            return $args;
        } catch (ReflectionException $e) {
        }

        throw new InternalServerErrorException('invalid_arguments', 'Invalid arguments');
    }

    /**
     * Set the name of the invocation method parameter used to receive the binding parameter
     * @param  string  $bindingParameterName  Binding parameter name
     * @return $this
     */
    public function setBindingParameterName(string $bindingParameterName): self
    {
        $arguments = $this->getArguments();

        if (!$arguments->get($bindingParameterName)) {
            throw new InternalServerErrorException(
                'cannot_find_binding_parameter',
                'The requested binding parameter did not exist on the invoke method'
            );
        }

        $this->bindingParameterName = $bindingParameterName;
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
     * Set the bound parameter on an instance of this operation
     * @param  PipeInterface|null  $parameter  Binding parameter
     * @return $this
     */
    public function setBoundParameter(?PipeInterface $parameter): self
    {
        $this->assertTransaction();

        if ($parameter instanceof PropertyValue) {
            $parameter = $parameter->getValue();
        }

        $this->boundParameter = $parameter;
        return $this;
    }

    /**
     * Set the URL inline parameters on an instance of this operation
     * @param  array  $inlineParameters  Inline parameters
     * @return $this
     */
    public function setInlineParameters(array $inlineParameters): self
    {
        $this->inlineParameters = $inlineParameters;
        return $this;
    }

    /**
     * Get the OData kind of this operation
     * @return string Kind
     */
    public function getKind(): string
    {
        switch (true) {
            case $this instanceof ActionInterface:
                return 'Action';

            case $this instanceof FunctionInterface:
                return 'Function';
        }

        throw new InternalServerErrorException('invalid_operation', 'Operations must implement as Function or Action');
    }

    /**
     * Get the arguments being provided by the transaction attached to this operation instance
     * @return array Arguments
     */
    public function getTransactionArguments(): array
    {
        switch (true) {
            case $this instanceof ActionInterface:
                $body = $this->transaction->getBody();

                if ($body && !is_array($body)) {
                    throw new BadRequestException('invalid_action_arguments',
                        'The arguments to the action were not correctly formed as an array');
                }

                return $body ?: [];

            case $this instanceof FunctionInterface:
                return $this->inlineParameters;
        }

        throw new InternalServerErrorException('invalid_operation', 'Operations must implement as Function or Action');
    }

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface {
        $lexer = new Lexer($currentSegment);

        try {
            $operationIdentifier = $lexer->identifier();
        } catch (LexerException $e) {
            throw new PathNotHandledException();
        }

        $operation = Lodata::getOperation($operationIdentifier);

        if (!$operation instanceof Operation) {
            throw new PathNotHandledException();
        }

        if ($nextSegment && $operation instanceof ActionInterface) {
            throw new BadRequestException(
                'cannot_compose_action',
                'It is not permitted to further compose the result of an action'
            );
        }

        if (!$argument && $operation->getBindingParameterName()) {
            throw new BadRequestException(
                'missing_bound_argument',
                'This operation is bound, but no bound argument was provided'
            );
        }

        $operation = clone $operation;
        $operation->setTransaction($transaction);
        $operation->setBoundParameter($argument);

        $inlineParameters = [];

        try {
            $inlineParameters = array_filter(explode(',', $lexer->matchingParenthesis()));

            $inlineParameters = Arr::collapse(array_map(function ($pair) use ($transaction) {
                $pair = trim($pair);

                $kv = array_map('trim', explode('=', $pair));

                if (count($kv) !== 2) {
                    throw new BadRequestException('invalid_arguments',
                        'The arguments provided to the operation were not valid');
                }

                list($key, $value) = $kv;

                if (strpos($value, '@') === 0) {
                    $value = $transaction->getParameterAlias($value);
                }

                return [$key => $value];
            }, $inlineParameters));
        } catch (LexerException $e) {
            if (!$nextSegment) {
                /** @var Argument $argument */
                foreach ($operation->getArguments() as $argument) {
                    $value = $transaction->getImplicitParameterAlias($argument->getName());

                    if (!$value) {
                        continue;
                    }

                    $inlineParameters[$argument->getName()] = $value;
                }
            }
        }

        $operation->setInlineParameters($inlineParameters);

        if ($operation instanceof ActionInterface) {
            $transaction->assertMethod(Request::METHOD_POST,
                'This operation must be addressed with a POST request');

            if ($transaction->getBody()) {
                $transaction->assertContentTypeJson();
            }
        }

        if ($operation instanceof FunctionInterface) {
            $transaction->assertMethod(Request::METHOD_GET,
                'This operation must be addressed with a GET request');

            $operation->getReflectedReturnType();
        }

        $bindingParameter = $operation->getBindingParameterName();
        $transactionArguments = $operation->getTransactionArguments();

        $arguments = [];

        /** @var Argument $argumentDefinition */
        foreach ($operation->getArguments() as $argumentDefinition) {
            $argumentName = $argumentDefinition->getName();
            if ($bindingParameter === $argumentName) {
                switch (true) {
                    case $argumentDefinition instanceof EntityArgument && !$operation->boundParameter instanceof Entity:
                    case $argumentDefinition instanceof EntitySetArgument && !$operation->boundParameter instanceof EntitySet:
                    case $argumentDefinition instanceof PrimitiveArgument && !$operation->boundParameter instanceof Primitive && !$operation->boundParameter instanceof PropertyValue:
                        throw new BadRequestException(
                            'invalid_bound_argument_type',
                            'The provided bound argument was not of the correct type for this function'
                        );
                }

                $arguments[] = $operation->boundParameter;
                continue;
            }

            switch (true) {
                case $argumentDefinition instanceof TransactionArgument:
                case $argumentDefinition instanceof EntitySetArgument:
                    $arguments[] = $argumentDefinition->generate($transaction);
                    break;

                case $argumentDefinition instanceof EntityArgument:
                    $arguments[] = $argumentDefinition->generate();
                    break;

                case $argumentDefinition instanceof PrimitiveArgument:
                    $arguments[] = $argumentDefinition->generate($transactionArguments[$argumentName] ?? null);
                    break;
            }
        }

        Gate::check(Gate::EXECUTE, $operation, $transaction, $arguments);

        $result = call_user_func_array([$operation, 'invoke'], array_values($arguments));

        if ($operation instanceof ActionInterface) {
            $returnPreference = $transaction->getPreferenceValue(Constants::RETURN);

            if ($returnPreference === Constants::MINIMAL) {
                throw NoContentException::factory()
                    ->header(Constants::PREFERENCE_APPLIED, Constants::RETURN.'='.Constants::MINIMAL);
            }
        }

        if ($operation instanceof FunctionInterface && null === $result) {
            throw new InternalServerErrorException(
                'missing_function_result',
                'Function is required to return a result'
            );
        }

        $returnType = $operation->getReturnType();
        $transaction->getRequest()->setMethod(Request::METHOD_GET);

        switch (true) {
            case $result === null && $operation->isNullable():
            case $returnType instanceof EntityType && $result->getType() instanceof $returnType:
            case $returnType instanceof PrimitiveType && $result instanceof Primitive:
                return $result;

            case $returnType instanceof PrimitiveType:
                return $returnType->instance($result);
        }

        throw new InternalServerErrorException(
            'invalid_return_type',
            'The operation returned an type that did not match its defined return type'
        );
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
     * Extract operation arguments for metadata
     * Ensure the binding parameter is first, if it exists. Filter out non-odata arguments.
     * @return ObjectArray|Argument[]
     */
    public function getExternalArguments()
    {
        return $this->getArguments()->sort(function (Argument $a, Argument $b) {
            if ($a->getName() === $this->getBindingParameterName()) {
                return -1;
            }

            if ($b->getName() === $this->getBindingParameterName()) {
                return 1;
            }

            return 0;
        })->filter(function ($argument) {
            if ($argument instanceof PrimitiveArgument) {
                return true;
            }

            if (($argument instanceof EntitySetArgument || $argument instanceof EntityArgument) && $this->getBindingParameterName() === $argument->getName()) {
                return true;
            }

            return false;
        });
    }
}
