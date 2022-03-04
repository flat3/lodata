<?php

declare(strict_types=1);

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\NoContentException;
use Flat3\Lodata\Interfaces\EntitySet\DeleteInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\EntitySet\UpdateInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Illuminate\Http\Request;

/**
 * Each
 * @package Flat3\Lodata\PathSegment
 */
class Each implements PipeInterface
{
    protected $argument;

    public function __construct(PipeInterface $argument)
    {
        $this->argument = $argument;
    }

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): PipeInterface {
        if ($currentSegment !== '$each') {
            throw new PathNotHandledException();
        }

        if (!$argument instanceof QueryInterface || !$argument instanceof EntitySet) {
            throw new PathNotHandledException();
        }

        if ($argument instanceof DeleteInterface && $transaction->getMethod() === Request::METHOD_DELETE) {
            foreach ($argument->query() as $entity) {
                $entity->delete($transaction);
            }

            throw new NoContentException();
        }

        if ($argument instanceof UpdateInterface && $transaction->getMethod() === Request::METHOD_PATCH) {
            foreach ($argument->query() as $entity) {
                $propertyValues = $argument->arrayToPropertyValues($transaction->getBodyAsArray());
                $argument->update($entity->getEntityId(), $propertyValues);
            }

            $argument->getTransaction()->getRequest()->setMethod(Request::METHOD_GET);

            return $argument;
        }

        return new self($argument);
    }

    public function getArgument(): PipeInterface
    {
        return $this->argument;
    }
}
