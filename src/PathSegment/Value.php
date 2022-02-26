<?php

declare(strict_types=1);

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\FoundException;
use Flat3\Lodata\Exception\Protocol\NoContentException;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\StreamInterface;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Type\Collection;
use Flat3\Lodata\Type\Stream;

/**
 * Value
 * @package Flat3\Lodata\PathSegment
 */
class Value implements PipeInterface, StreamInterface
{
    /**
     * The primitive provided to this path segment
     * @var Primitive $primitive
     */
    protected $primitive;

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface {
        if ($currentSegment !== '$value') {
            throw new PathNotHandledException();
        }

        $result = new self();

        switch (true) {
            case $argument instanceof PropertyValue:
                $result->primitive = $argument->getPrimitive();
                return $result;

            case $argument instanceof Primitive:
                $result->primitive = $argument;
                return $result;

            case $argument === null:
                throw new NoContentException();
        }

        throw new BadRequestException(
            'bad_value_argument',
            '$value was not passed a valid argument',
        );
    }

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        $primitive = $this->primitive;

        if ($primitive && null === $primitive->get()) {
            throw new NoContentException('null_value');
        }

        if ($primitive instanceof Collection) {
            throw new BadRequestException('invalid_property', 'A collection cannot be requested as a raw value');
        }

        if ($primitive instanceof Stream && $primitive->getReadLink()) {
            throw new FoundException((string) $primitive->getReadLink());
        }

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emitStream($transaction);
        });
    }

    public function emitStream(Transaction $transaction): void
    {
        $transaction->sendOutput((string) $this->primitive->toJson());
    }
}
