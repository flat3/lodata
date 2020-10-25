<?php

namespace Flat3\Lodata\PathComponent;

use Countable;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Interfaces\EmitInterface;
use Flat3\Lodata\Interfaces\PipeInterface;

class Count implements EmitInterface, PipeInterface
{
    /** @var Countable */
    protected $countable;

    public function __construct(Countable $countable)
    {
        $this->countable = $countable;
    }

    public function emit(Transaction $transaction): void
    {
        $transaction->outputRaw($this->countable->count());
    }

    public function response(Transaction $transaction): Response
    {
        $transaction->configureTextResponse();

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $this->emit($transaction);
        });
    }

    public static function pipe(
        Transaction $transaction,
        string $currentComponent,
        ?string $nextComponent,
        ?PipeInterface $argument
    ): ?PipeInterface {
        if ($currentComponent !== '$count') {
            throw new PathNotHandledException();
        }

        if (!$argument instanceof Countable) {
            throw new BadRequestException('not_countable', '$count was passed something not countable');
        }

        $transaction->getTop()->clearValue();
        $transaction->getSkip()->clearValue();
        $transaction->getOrderBy()->clearValue();
        $transaction->getExpand()->clearValue();

        return new static($argument);
    }
}
