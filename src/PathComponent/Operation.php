<?php

namespace Flat3\OData\PathComponent;

use Closure;
use Flat3\OData\ActionOperation;
use Flat3\OData\Controller\Transaction;
use Flat3\OData\Entity;
use Flat3\OData\EntitySet;
use Flat3\OData\EntityType;
use Flat3\OData\Exception\Internal\LexerException;
use Flat3\OData\Exception\Internal\PathNotHandledException;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Exception\Protocol\InternalServerErrorException;
use Flat3\OData\Exception\Protocol\NotImplementedException;
use Flat3\OData\Expression\Lexer;
use Flat3\OData\FunctionOperation;
use Flat3\OData\Helper\ObjectArray;
use Flat3\OData\Interfaces\EntityTypeInterface;
use Flat3\OData\Interfaces\NamedInterface;
use Flat3\OData\Interfaces\PipeInterface;
use Flat3\OData\Interfaces\ResourceInterface;
use Flat3\OData\Interfaces\ServiceInterface;
use Flat3\OData\Interfaces\TypeInterface;
use Flat3\OData\Model;
use Flat3\OData\Operation\Argument;
use Flat3\OData\Operation\EntityArgument;
use Flat3\OData\Operation\EntitySetArgument;
use Flat3\OData\Operation\PrimitiveTypeArgument;
use Flat3\OData\Operation\TransactionArgument;
use Flat3\OData\PrimitiveType;
use Flat3\OData\Traits\HasName;
use Flat3\OData\Traits\HasTitle;
use Flat3\OData\Traits\HasType;
use Illuminate\Http\Request;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;

abstract class Operation implements ServiceInterface, ResourceInterface, TypeInterface, NamedInterface, PipeInterface
{
    use HasName;
    use HasType;
    use HasTitle;

    /** @var callable $callback */
    protected $callback;

    /** @var string $bindingParameter */
    protected $bindingParameter;

    public function __construct($name)
    {
        $this->setName($name);
    }

    public function getReflectedReturnType(): string
    {
        try {
            $rfc = new ReflectionFunction($this->callback);

            /** @var ReflectionNamedType $rt */
            $rt = $rfc->getReturnType();

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
        $tn = $this->getReflectedReturnType();

        switch (true) {
            case is_a($tn, EntitySet::class, true);
                return true;

            case is_a($tn, Entity::class, true);
            case is_a($tn, PrimitiveType::class, true);
                return false;
        }

        throw new InternalServerErrorException('invalid_return_type', 'Invalid return type');
    }

    public function isNullable(): bool
    {
        try {
            $rfn = new ReflectionFunction($this->callback);
            return !$rfn->hasReturnType() || $rfn->getReturnType()->allowsNull() || $rfn->getReturnType()->getName() === 'void';
        } catch (ReflectionException $e) {
            return false;
        }
    }

    public function getArguments(): ObjectArray
    {
        if (!$this->callback) {
            throw new InternalServerErrorException('missing_callback', 'Missing callback for Operation');
        }

        try {
            $rfn = new ReflectionFunction($this->callback);
            $args = new ObjectArray();

            foreach ($rfn->getParameters() as $parameter) {
                $args[] = Argument::factory($parameter);
            }

            return $args;
        } catch (ReflectionException $e) {
        }

        throw new InternalServerErrorException('invalid_arguments', 'Invalid arguments');
    }

    public function setCallback(callable $callback): self
    {
        $this->callback = $callback;

        $returnType = $this->getReflectedReturnType();

        if (is_a($returnType, PrimitiveType::class, true)) {
            $this->setType($returnType::factory());
        }

        return $this;
    }

    public function setBindingParameter(string $bindingParameter): self
    {
        if (!$this->callback) {
            throw new InternalServerErrorException(
                'no_callback',
                'The callback must be defined before setting the binding parameter'
            );
        }

        $arguments = $this->getArguments();

        if (!$arguments->get($bindingParameter)) {
            throw new InternalServerErrorException(
                'cannot_find_binding_parameter',
                'The requested binding parameter did not exist on the provided callback'
            );
        }

        $this->bindingParameter = $bindingParameter;
        return $this;
    }

    public function getBindingParameter(): ?string
    {
        return $this->bindingParameter;
    }

    public function invoke(array $args): ?PipeInterface
    {
        if (!$this->callback instanceof Closure) {
            throw new NotImplementedException('no_callback', 'The requested operation has no implementation');
        }

        return call_user_func_array($this->callback, $args);
    }

    public static function pipe(
        Transaction $transaction,
        string $currentComponent,
        ?string $nextComponent,
        ?PipeInterface $argument
    ): ?PipeInterface {
        $lexer = new Lexer($currentComponent);
        try {
            $operationIdentifier = $lexer->odataIdentifier();
            $args = $lexer->matchingParenthesis();
        } catch (LexerException $e) {
            throw new PathNotHandledException();
        }

        $model = Model::get();

        $operation = $model->getResources()->get($operationIdentifier);

        if (!$operation instanceof Operation) {
            throw new PathNotHandledException();
        }

        if ($operation instanceof ActionOperation) {
            $transaction->ensureMethod(Request::METHOD_POST, 'This operation must be addressed with a POST request');
        }

        if ($nextComponent && $operation instanceof ActionOperation) {
            throw new BadRequestException(
                'cannot_compose_action',
                'It is not permitted to further compose the result of an action'
            );
        }

        $bindingParameter = $operation->getBindingParameter();

        if ($bindingParameter && !$argument) {
            throw new BadRequestException(
                'missing_bound_argument',
                'This operation is bound, but no bound argument was provided'
            );
        }

        $transactionArguments = array_merge(...array_map(function ($pair) use ($transaction) {
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
        }, array_filter(explode(',', $args))));

        $operationArguments = [];

        /** @var Argument $argumentDefinition */
        foreach ($operation->getArguments() as $argumentDefinition) {
            if ($bindingParameter === $argumentDefinition->getName()) {
                switch (true) {
                    case $argumentDefinition instanceof EntityArgument && !$argument instanceof Entity:
                    case $argumentDefinition instanceof EntitySetArgument && !$argument instanceof EntitySet:
                    case $argumentDefinition instanceof PrimitiveTypeArgument && !$argument instanceof PrimitiveType:
                        throw new BadRequestException(
                            'invalid_bound_argument_type',
                            'The provided bound argument was not of the correct type for this function'
                        );
                }

                $operationArguments[] = $argument;
                continue;
            }

            switch (true) {
                case $argumentDefinition instanceof TransactionArgument:
                    $operationArguments[] = $argumentDefinition->generate($transaction);
                    break;

                case $argumentDefinition instanceof EntitySetArgument:
                    $operationArguments[] = $argumentDefinition->generate($transaction);
                    break;

                case $argumentDefinition instanceof EntityArgument:
                    $operationArguments[] = $argumentDefinition->generate();
                    break;

                case $argumentDefinition instanceof PrimitiveTypeArgument:
                    $operationArguments[] = $argumentDefinition->generate($transactionArguments[$argumentDefinition->getName()] ?? null);
                    break;
            }
        }

        $result = $operation->invoke($operationArguments);

        $returnType = $operation->getType();

        if (null === $result && $operation instanceof FunctionOperation) {
            throw new InternalServerErrorException(
                'missing_function_result',
                'Function is required to return a result'
            );
        }

        switch (true) {
            case $result === null && $operation->isNullable():
            case $returnType instanceof EntityType && $result instanceof EntityTypeInterface && $result->getType() instanceof $returnType:
            case $returnType instanceof PrimitiveType && $result instanceof $returnType:
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
}
