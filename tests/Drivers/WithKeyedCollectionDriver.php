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

        $collection = collect($this->getSeed());

        $entityType = new EntityType('passenger');
        $entityType->setOpen();
        $entityType->setKey(new DeclaredProperty('id', Type::string()));
        $this->addPassengerProperties($entityType);
        $entityType->getDeclaredProperty('name')->setSearchable();
        $entitySet = new CollectionEntitySet($this->entitySet, $entityType);
        $entitySet->setCollection($collection);

        Lodata::add($entitySet);
        $this->updateETag();
        $this->keepDriverState();
    }

    protected function tearDownDriver(): void
    {
        $this->assertDriverStateDiffSnapshot();
    }

    protected function captureDriverState(): array
    {
        return Lodata::getEntitySet($this->entitySet)->getCollection()->toArray();
    }
}