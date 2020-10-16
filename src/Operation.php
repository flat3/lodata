<?php

namespace Flat3\Lodata;

use Closure;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\LexerException;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Interfaces\EntityTypeInterface;
use Flat3\Lodata\Interfaces\InstanceInterface;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Interfaces\ServiceInterface;
use Flat3\Lodata\Interfaces\TypeInterface;
use Flat3\Lodata\Operation\Argument;
use Flat3\Lodata\Operation\EntityArgument;
use Flat3\Lodata\Operation\EntitySetArgument;
use Flat3\Lodata\Operation\PrimitiveTypeArgument;
use Flat3\Lodata\Operation\TransactionArgument;
use Flat3\Lodata\Traits\HasIdentifier;
use Flat3\Lodata\Traits\HasTitle;
use Flat3\Lodata\Traits\HasType;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;

abstract class Operation implements ServiceInterface, ResourceInterface, TypeInterface, IdentifierInterface, PipeInterface, InstanceInterface
{
    use HasIdentifier;
    use HasType;
    use HasTitle;

    /** @var callable $callback */
    protected $callback;

    /** @var string $bindingParameterName */
    protected $bindingParameterName;

    /** @var ?PipeInterface $boundParameter */
    protected $boundParameter;

    /** @var array $inlineParameters */
    protected $inlineParameters = [];

    /** @var Transaction $transaction */
    protected $transaction;

    public function __construct($name)
    {
        $this->setIdentifier($name);
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

    public function setBindingParameterName(string $bindingParameterName): self
    {
        if (!$this->callback) {
            throw new InternalServerErrorException(
                'no_callback',
                'The callback must be defined before setting the binding parameter'
            );
        }

        $arguments = $this->getArguments();

        if (!$arguments->get($bindingParameterName)) {
            throw new InternalServerErrorException(
                'cannot_find_binding_parameter',
                'The requested binding parameter did not exist on the provided callback'
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
        $this->ensureInstance();
        $this->boundParameter = $parameter;
        return $this;
    }

    public function setInlineParameters(array $inlineParameters): self
    {
        $this->inlineParameters = $inlineParameters;
        return $this;
    }

    abstract public function getTransactionArguments(): array;

    public function invoke(): ?PipeInterface
    {
        if (!$this->callback instanceof Closure) {
            throw new NotImplementedException('no_callback', 'The requested operation has no implementation');
        }

        $bindingParameter = $this->getBindingParameterName();
        $transactionArguments = $this->getTransactionArguments();

        $arguments = [];

        /** @var Argument $argumentDefinition */
        foreach ($this->getArguments() as $argumentDefinition) {
            $argumentName = $argumentDefinition->getName();
            if ($bindingParameter === $argumentName) {
                switch (true) {
                    case $argumentDefinition instanceof EntityArgument && !$this->boundParameter instanceof Entity:
                    case $argumentDefinition instanceof EntitySetArgument && !$this->boundParameter instanceof EntitySet:
                    case $argumentDefinition instanceof PrimitiveTypeArgument && !$this->boundParameter instanceof PrimitiveType:
                        throw new BadRequestException(
                            'invalid_bound_argument_type',
                            'The provided bound argument was not of the correct type for this function'
                        );
                }

                $arguments[] = $this->boundParameter;
                continue;
            }

            switch (true) {
                case $argumentDefinition instanceof TransactionArgument:
                    $arguments[] = $argumentDefinition->generate($this->transaction);
                    break;

                case $argumentDefinition instanceof EntitySetArgument:
                    $arguments[] = $argumentDefinition->generate($this->transaction);
                    break;

                case $argumentDefinition instanceof EntityArgument:
                    $arguments[] = $argumentDefinition->generate();
                    break;

                case $argumentDefinition instanceof PrimitiveTypeArgument:
                    $arguments[] = $argumentDefinition->generate($transactionArguments[$argumentName] ?? null);
                    break;
            }
        }

        return call_user_func_array($this->callback, array_values($arguments));
    }

    public static function pipe(
        Transaction $transaction,
        string $currentComponent,
        ?string $nextComponent,
        ?PipeInterface $argument
    ): ?PipeInterface
    {
        $lexer = new Lexer($currentComponent);

        try {
            $operationIdentifier = $lexer->identifier();
        } catch (LexerException $e) {
            throw new PathNotHandledException();
        }

        $model = Model::get();

        $operation = $model->getResources()->get($operationIdentifier);

        if (!$operation instanceof Operation) {
            throw new PathNotHandledException();
        }

        if ($nextComponent && $operation instanceof ActionOperation) {
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

        $operation = $operation->asInstance($transaction);
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
            if (!$nextComponent) {
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

        $result = $operation->invoke();

        $returnType = $operation->getType();

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
        return Transaction::getResourceUrl() . $this->getIdentifier();
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
