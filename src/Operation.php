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
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Helper\PropertyValue;
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
use Flat3\Lodata\Traits\HasIdentifier;
use Flat3\Lodata\Traits\HasTitle;
use Flat3\Lodata\Traits\HasTransaction;
use Illuminate\Http\Request;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;

abstract class Operation implements ServiceInterface, ResourceInterface, IdentifierInterface, PipeInterface
{
    use HasIdentifier;
    use HasTitle;
    use HasTransaction;

    /** @var string $bindingParameterName */
    protected $bindingParameterName;

    /** @var ?PipeInterface $boundParameter */
    protected $boundParameter;

    /** @var array $inlineParameters */
    protected $inlineParameters = [];

    /** @var Type $returnType */
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

    public function getReturnType(): ?Type
    {
        if ($this->returnType) {
            return $this->returnType;
        }

        if (is_a($this->getReflectedReturnType(), Primitive::class, true)) {
            return new PrimitiveType($this->getReflectedReturnType());
        }

        return null;
    }

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

    public function returnsCollection(): bool
    {
        $returnType = $this->getReflectedReturnType();

        switch (true) {
            case is_a($returnType, EntitySet::class, true);
                return true;

            case is_a($returnType, Entity::class, true);
            case is_a($returnType, Primitive::class, true);
                return false;
        }

        throw new InternalServerErrorException('invalid_return_type', 'Invalid return type');
    }

    public function isNullable(): bool
    {
        try {
            $rfn = new ReflectionMethod($this, 'invoke');
            return !$rfn->hasReturnType() || $rfn->getReturnType()->allowsNull() || $rfn->getReturnType()->getName() === 'void';
        } catch (ReflectionException $e) {
            return false;
        }
    }

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

    public function getBindingParameterName(): ?string
    {
        return $this->bindingParameterName;
    }

    public function setBoundParameter(?PipeInterface $parameter): self
    {
        $this->ensureTransaction();

        if ($parameter instanceof PropertyValue) {
            $parameter = $parameter->getValue();
        }

        $this->boundParameter = $parameter;
        return $this;
    }

    public function setInlineParameters(array $inlineParameters): self
    {
        $this->inlineParameters = $inlineParameters;
        return $this;
    }

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

            $inlineParameters = array_merge(...array_map(function ($pair) use ($transaction) {
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
            $transaction->ensureMethod(Request::METHOD_POST,
                'This operation must be addressed with a POST request');

            if ($transaction->getBody()) {
                $transaction->ensureContentTypeJson();
            }
        }

        if ($operation instanceof FunctionInterface) {
            $transaction->ensureMethod(Request::METHOD_GET,
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

        switch (true) {
            case $result === null && $operation->isNullable():
            case $returnType instanceof EntityType && $result->getType() instanceof $returnType:
            case $returnType instanceof PrimitiveType && $result instanceof Primitive:
                return $result;
        }

        throw new InternalServerErrorException(
            'invalid_return_type',
            'The operation returned an type that did not match its defined return type'
        );
    }

    public function getResourceUrl(): string
    {
        return Transaction::getResourceUrl().$this->getName();
    }

    public function setReturnType(Type $type): self
    {
        $this->returnType = $type;
        return $this;
    }
}
