<?php

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Entity;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\FoundException;
use Flat3\Lodata\Exception\Protocol\NoContentException;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\EmitInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\MediaEntity;
use Flat3\Lodata\Primitive;

/**
 * Value
 * @package Flat3\Lodata\PathSegment
 */
class Value implements PipeInterface, EmitInterface
{
    /**
     * The primitive provided to this path segment
     * @var Primitive $primitive
     */
    protected $primitive;

    /**
     * The media entity provided to this path segment
     * @var MediaEntity $entity
     */
    protected $entity;

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface {
        if ($currentSegment !== '$value') {
            throw new PathNotHandledException();
        }

        if ($argument instanceof MediaEntity) {
            $result = new self();
            $result->entity = $argument;

            return $result;
        }

        if ($argument instanceof Entity) {
            throw new BadRequestException(
                'bad_value_entity_argument',
                '$value was passed an entity that is not a media entity'
            );
        }

        if ($argument instanceof PropertyValue) {
            $value = $argument->getPrimitiveValue();

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

        if ($this->entity) {
            throw new FoundException($this->entity->getReadLink());
        }

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emit($transaction);
        });
    }

    public function emit(Transaction $transaction): void
    {
        if ($this->primitive) {
            $transaction->outputRaw($this->primitive->get());
        }
    }
}
