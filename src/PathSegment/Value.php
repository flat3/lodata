<?php

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\FoundException;
use Flat3\Lodata\Exception\Protocol\NoContentException;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\EmitStreamInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\Type\Stream;

/**
 * Value
 * @package Flat3\Lodata\PathSegment
 */
class Value implements PipeInterface, EmitStreamInterface
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

        if ($argument instanceof PropertyValue) {
            $value = $argument->getPrimitiveValue();

            if ($value instanceof Stream) {
                throw new FoundException($value->getReadLink());
            }

            if (null === $value->get()) {
                throw new NoContentException('no_content', 'No content');
            }

            $result = new self();
            $result->primitive = $value;

            return $result;
        }

        throw new BadRequestException(
            'bad_value_argument',
            '$value was not passed a valid argument',
        );
    }

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        if ($this->primitive && null === $this->primitive->get()) {
            throw new NoContentException('null_value');
        }

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emitStream($transaction);
        });
    }

    public function emitStream(Transaction $transaction): void
    {
        if ($this->primitive) {
            $transaction->sendOutput($this->primitive->toJson());
        }
    }
}
