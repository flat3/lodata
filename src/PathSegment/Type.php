<?php

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Entity;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\PipeInterface;

/**
 * Type
 * @package Flat3\Lodata\PathSegment
 */
class Type implements PipeInterface
{
    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): PipeInterface {
        $entityType = Lodata::getEntityType($currentSegment);

        if (!$entityType) {
            throw new PathNotHandledException();
        }

        if (!$argument instanceof Entity) {
            throw new PathNotHandledException();
        }

        if ($argument->getType()->getIdentifier() !== $entityType->getIdentifier()) {
            throw new NotFoundException('invalid_entity_type', 'The provided type did not match the entity type');
        }

        return $argument;
    }
}
