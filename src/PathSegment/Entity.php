<?php

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Entity
 * @package Flat3\Lodata\PathSegment
 */
class Entity implements PipeInterface
{
    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): PipeInterface {
        if ($currentSegment !== '$entity') {
            throw new PathNotHandledException();
        }

        if ($argument) {
            throw new BadRequestException('entity_argument', 'Entity cannot have a preceding path segment');
        }

        $id = $transaction->getIdOption();

        if (!$id->hasValue()) {
            throw new BadRequestException('missing_id', 'The entity id system query option must be provided');
        }

        $entityId = $id->getValue();
        if (Str::startsWith($entityId, ServiceProvider::endpoint())) {
            $entityId = Str::substr($entityId, strlen(ServiceProvider::endpoint()));
        }

        return EntitySet::pipe($transaction, $entityId);
    }
}
