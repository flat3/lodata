<?php

declare(strict_types=1);

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\NotImplementedException;
use Flat3\Lodata\Helper\PropertyValue;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\EntitySet\CountInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\StreamInterface;

/**
 * Count
 * @package Flat3\Lodata\PathSegment
 */
class Count implements StreamInterface, PipeInterface
{
    /**
     * The countable value passed to this segment
     * @var CountInterface Countable
     */
    protected $countable;

    public function __construct(CountInterface $countable)
    {
        $this->countable = $countable;
    }

    public function emitStream(Transaction $transaction): void
    {
        $transaction->sendOutput((string) $this->countable->count());
    }

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emitStream($transaction);
        });
    }

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface {
        if ($currentSegment !== '$count') {
            throw new PathNotHandledException();
        }

        if ($argument instanceof PropertyValue) {
            $argument = $argument->getValue();
        }

        if (!$argument instanceof EntitySet || !$argument instanceof CountInterface) {
            throw new NotImplementedException('not_countable', '$count was passed something not countable');
        }

        $argument->setApplyQueryOptions(true);
        $transaction->getTop()->clearValue();
        $transaction->getSkip()->clearValue();
        $transaction->getOrderBy()->clearValue();
        $transaction->getExpand()->clearValue();

        return new self($argument);
    }
}
