<?php

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\NoContentException;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\EmitInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
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

    public function __construct(Primitive $primitive)
    {
        $this->primitive = $primitive;
    }

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface {
        if ($currentSegment !== '$value') {
            throw new PathNotHandledException();
        }

        if (!$argument instanceof PropertyValue) {
            throw new BadRequestException('bad_value_argument',
                '$value must be passed a property value');
        }

        $value = $argument->getPrimitiveValue();

        if (null === $value->get()) {
            throw new NoContentException('no_content', 'No content');
        }

        return new self($value);
    }

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        if (null === $this->primitive->get()) {
            throw new NoContentException('null_value');
        }

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emit($transaction);
        });
    }

    public function emit(Transaction $transaction): void
    {
        $transaction->outputRaw($this->primitive->get());
    }
}
