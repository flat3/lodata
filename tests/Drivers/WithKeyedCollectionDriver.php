<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Drivers;

use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\CollectionEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Type;

trait WithKeyedCollectionDriver
{
    protected function setUpDriver(): void
    {
        $this->entityId = 'alpha';
        $this->missingEntityId = 'notfound';
        $this->etag = 'W/"bc1beaef1f65da0e00aceab2d8af7224e08c4173772bfb721195bd0bd94c8de7"';

        $collection = collect($this->getSeed());

        $entityType = new EntityType('passenger');
        $entityType->setKey(new DeclaredProperty('id', Type::string()));
        $this->addPassengerProperties($entityType);
        $entityType->getDeclaredProperty('name')->setSearchable();
        $entitySet = new CollectionEntitySet($this->entitySet, $entityType);
        $entitySet->setCollection($collection);

        Lodata::add($entitySet);
        $this->keepDriverState();
    }

    protected function tearDownDriver(): void
    {
        $this->assertDriverStateDiffSnapshot();
    }

    protected function captureDriverState():array {
        return Lodata::getEntitySet($this->entitySet)->getCollection()->toArray();
    }
}