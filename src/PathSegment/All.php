<?php

declare(strict_types=1);

namespace Flat3\Lodata\PathSegment;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\Gate;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\EntitySet\QueryInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\StreamInterface;
use Flat3\Lodata\Singleton;
use Generator;
use Illuminate\Http\Request;

/**
 * All
 * @package Flat3\Lodata\PathSegment
 */
class All implements StreamInterface, PipeInterface, ContextInterface
{
    protected $entityType;

    public function __construct(?EntityType $entityType = null)
    {
        $this->entityType = $entityType;
    }

    public function emitStream(Transaction $transaction): void
    {
        $transaction->outputJsonArrayStart();
        $emitSeparator = false;

        /** @var EntitySet[] $entitySets */
        $entitySets = Lodata::getResources()
            ->sliceByClass(EntitySet::class)
            ->filter(function (EntitySet $entitySet) {
                return !$this->entityType || ($entitySet->getType()->getIdentifier() === $this->entityType->getIdentifier());
            })
            ->all();

        while ($entitySets) {
            $entitySet = clone array_pop($entitySets);

            if (!$entitySet instanceof QueryInterface) {
                continue;
            }

            if (!Gate::query($entitySet, $transaction)->allows()) {
                continue;
            }

            $entitySet->setTransaction($transaction);

            /** @var Generator $results */
            $results = $entitySet->query();

            while ($results->valid()) {
                if ($emitSeparator) {
                    $transaction->outputJsonSeparator();
                }

                $emitSeparator = true;

                /** @var Entity $entity */
                $entity = $results->current();

                $entity->emitJson($transaction);
                $results->next();

                if (!$results->valid()) {
                    break;
                }
            }
        }

        /** @var Singleton[] $singletons */
        $singletons = Lodata::getResources()
            ->sliceByClass(Singleton::class)
            ->filter(function (Singleton $singleton) {
                return !$this->entityType || ($singleton->getType()->getIdentifier() === $this->entityType->getIdentifier());
            })
            ->all();

        while ($singletons) {
            $singleton = clone array_pop($singletons);

            if (!Gate::read($singleton, $transaction)->allows()) {
                continue;
            }

            $singleton->setTransaction($transaction);

            if ($emitSeparator) {
                $transaction->outputJsonSeparator();
            }

            $emitSeparator = true;

            $singleton->emitJson($transaction);
        }

        $transaction->outputJsonArrayEnd();
    }

    public function response(Transaction $transaction, ?ContextInterface $context = null): Response
    {
        $transaction->assertMethod(Request::METHOD_GET);

        return $transaction->getResponse()->setCallback(function () use ($transaction) {
            $metadataContainer = $transaction->createMetadataContainer();
            $metadataContainer['context'] = $this->getContextUrl($transaction);

            $transaction->outputJsonObjectStart();

            if ($metadataContainer->hasProperties()) {
                $transaction->outputJsonKV($metadataContainer->getProperties());
                $transaction->outputJsonSeparator();
            }

            $transaction->outputJsonKey('value');

            $this->emitStream($transaction);

            $transaction->outputJsonObjectEnd();
        });
    }

    public static function pipe(
        Transaction $transaction,
        string $currentSegment,
        ?string $nextSegment,
        ?PipeInterface $argument
    ): ?PipeInterface {
        if ($argument instanceof All) {
            return $argument;
        }

        if ($currentSegment !== '$all') {
            throw new PathNotHandledException();
        }

        if ($argument) {
            throw new BadRequestException('all_argument', '$all must be the first argument in the path');
        }

        if (!$nextSegment) {
            return new self();
        }

        $entityType = Lodata::getEntityType($nextSegment);

        if (!$entityType) {
            throw new BadRequestException('bad_entity_type', 'The requested entity type does not exist');
        }

        return new self($entityType);
    }

    /**
     * Get the context URL for this segment
     * @param  Transaction  $transaction  Related transaction
     * @return string Context URL
     */
    public function getContextUrl(Transaction $transaction): string
    {
        return sprintf('%s#Collection(%s)', $transaction->getContextUrl(), EntityType::identifier);
    }
}
