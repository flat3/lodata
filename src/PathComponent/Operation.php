<?php

namespace Flat3\OData\PathComponent;

use Closure;
use Flat3\OData\Controller\Transaction;
use Flat3\OData\Entity;
use Flat3\OData\EntitySet;
use Flat3\OData\Exception\Internal\LexerException;
use Flat3\OData\Exception\Internal\PathNotHandledException;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Exception\Protocol\InternalServerErrorException;
use Flat3\OData\Exception\Protocol\NotImplementedException;
use Flat3\OData\Expression\Lexer;
use Flat3\OData\Helper\Argument;
use Flat3\OData\Helper\ObjectArray;
use Flat3\OData\Interfaces\PipeInterface;
use Flat3\OData\Interfaces\ResourceInterface;
use Flat3\OData\Interfaces\TypeInterface;
use Flat3\OData\Model;
use Flat3\OData\PrimitiveType;
use Flat3\OData\Traits\HasName;
use Flat3\OData\Traits\HasType;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use RuntimeException;

abstract class Operation implements ResourceInterface, TypeInterface, PipeInterface
{
    use HasName;
    use HasType;

    /** @var callable $callback */
    protected $callback;

    public function __construct($identifier)
    {
        $this->setName($identifier);
    }

    public function returnsCollection(): bool
    {
        try {
            $rfc = new ReflectionFunction($this->callback);

            /** @var ReflectionNamedType $rt */
            $rt = $rfc->getReturnType();
            $tn = $rt->getName();
            switch (true) {
                case is_a($tn, EntitySet::class, true);
                    return true;

                case is_a($tn, Entity::class, true);
                case is_a($tn, PrimitiveType::class, true);
                    return false;
            }
        } catch (ReflectionException $e) {
        }

        throw new RuntimeException('Invalid return type');
    }

    public function isNullable(): bool
    {
        try {
            $rfn = new ReflectionFunction($this->callback);
            return $rfn->getReturnType()
                ->allowsNull();
        } catch (ReflectionException $e) {
            return false;
        }
    }

    public function getArguments(): ObjectArray
    {
        try {
            $rfn = new ReflectionFunction($this->callback);
            $args = new ObjectArray();

            foreach ($rfn->getParameters() as $parameter) {
                $type = $parameter->getType()->getName();
                $arg = new Argument($parameter->getName(), $type::factory(), $parameter->allowsNull());
                $args[] = $arg;
            }

            return $args;
        } catch (ReflectionException $e) {
        }

        throw new RuntimeException('Invalid arguments');
    }

    public function setCallback(callable $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    public function invoke(array $args): PipeInterface
    {
        if (!$this->callback instanceof Closure) {
            throw new NotImplementedException('no_callback', 'The requested operation has no implementation');
        }

        return call_user_func_array($this->callback, $args);
    }

    public static function pipe(
        Transaction $transaction,
        string $pathComponent,
        ?PipeInterface $argument
    ): ?PipeInterface {
        $lexer = new Lexer($pathComponent);
        try {
            $operationIdentifier = $lexer->odataIdentifier();
            $args = $lexer->matchingParenthesis();
        } catch (LexerException $e) {
            throw new PathNotHandledException();
        }

        /** @var Model $model */
        $model = app()->make(Model::class);

        $operation = $model->getResources()->get($operationIdentifier);

        if (!$operation instanceof Operation) {
            throw new PathNotHandledException();
        }

        $providedArguments = array_merge(...array_map(function ($pair) use ($transaction) {
            $pair = trim($pair);

            $kv = array_map('trim', explode('=', $pair));

            if (count($kv) !== 2) {
                throw new BadRequestException('invalid_arguments',
                    'The arguments provided to the operation were not valid');
            }

            list($key, $value) = $kv;

            if (strpos($value, '@') === 0) {
                $value = $transaction->getReferencedValue($value);
            }

            return [$key => $value];
        }, array_filter(explode(',', $args))));

        $parsedArguments = [];

        /** @var Argument $argumentDefinition */
        foreach ($operation->getArguments() as $argumentDefinition) {
            $argumentIdentifier = $argumentDefinition->getName();
            if (!array_key_exists($argumentIdentifier, $providedArguments)) {
                if (!$argumentDefinition->isNullable()) {
                    throw new BadRequestException('non_null_argument_missing',
                        sprintf('A non-null argument (%s) is missing', $argumentIdentifier));
                }

                $parsedArguments[$argumentIdentifier] = $argumentDefinition->getType()::factory(null);
                continue;
            }

            $lexer = new Lexer($providedArguments[$argumentIdentifier]);

            try {
                $parsedArguments[$argumentIdentifier] = $lexer->type($argumentDefinition->getType());
            } catch (LexerException $e) {
                throw new BadRequestException(
                    'invalid_argument_type',
                    sprintf(
                        'The provided argument %s was not of type %s',
                        $argumentIdentifier,
                        $argumentDefinition->getType()->getName()
                    )
                );
            }
        }

        $result = $operation->invoke($parsedArguments);

        $returnType = $operation->getType();

        switch (true) {
            case $result === null && !$operation->isNullable():
            case $returnType instanceof Entity && !$result->getEntityType() instanceof $returnType:
            case $returnType instanceof PrimitiveType && !$result instanceof $returnType:
                throw new InternalServerErrorException(
                    'invalid_return_type',
                    'The operation returned an type that did not match its defined return type'
                );
        }

        return $result;
    }
}
