<?php

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\EmitInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ReferenceInterface;

/**
 * Reference
 * @package Flat3\Lodata\PathSegment
 */
class Reference implements EmitInterface, PipeInterface
{
    /**
     * The referencable item passed to this path segment
     * @var Entity|EntitySet $referencable
     */
    protected $referencable;

    public function __construct($countable)
    {
        $this->referencable = $countable;
    }

    public function emit(Transaction $transaction): void
    {
        $this->referencable->emit($transaction);
    }

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        return $this->referencable->response($transaction, $context);
    }

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface {
        if ($currentSegment !== '$ref') {
            throw new PathNotHandledException();
        }

        if ($nextSegment) {
            throw new BadRequestException('no_next_segment', 'Reference request must be the last segment');
        }

        if (!$argument instanceof Entity && !$argument instanceof EntitySet) {
            throw new NotFoundException(
                'not_entity_or_entity_set',
                'Can only ask for a reference for an entity set or entity'
            );
        }

        /** @var ReferenceInterface $argument */
        $argument->useReferences();

        return new static($argument);
    }
}
