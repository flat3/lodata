<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Drivers;

use Flat3\Lodata\Drivers\MongoEntitySet;
use Flat3\Lodata\Drivers\MongoEntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Helpers\MockMongoCollection;
use MongoDB;

trait WithMongoDriver
{
    protected function setUpDriver(): void
    {
        $this->entitySetKey = '_id';
        $this->entityId = 'alpha';
        $this->missingEntityId = 'missing';

        $collection = (new MongoDB\Client)->test->passengers;
        $collection->drop();
        $mockCollection = new MockMongoCollection(
            $collection->getManager(),
            $collection->getDatabaseName(),
            $collection->getCollectionName()
        );

        $entityType = new MongoEntityType('passenger');
        $this->addPassengerProperties($entityType);
        $entitySet = new MongoEntitySet($this->entitySet, $entityType);
        $entitySet->setCollection($mockCollection);
        Lodata::add($entitySet);

        foreach ($this->getSeed() as $key => $record) {
            $entitySet->create($entitySet->arrayToPropertyValues(array_merge(['_id' => $key], $record)));
        }

        $this->updateETag();
        $this->keepDriverState();
    }

    protected function tearDownDriver(): void
    {
        $this->assertDriverStateDiffSnapshot();
    }

    protected function captureDriverState(): array
    {
        $data = [];

        $collection = (new MongoDB\Client)->test->passengers;
        $mockCollection = new MockMongoCollection(
            $collection->getManager(),
            $collection->getDatabaseName(),
            $collection->getCollectionName()
        );

        foreach ($mockCollection->find() as $key => $document) {
            $data[$key] = $document;
        }

        return $data;
    }
}