<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Drivers;

use Flat3\Lodata\Annotation\Core\V1\ComputedDefaultValue;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\Drivers\CollectionEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Type;

trait WithNumericCollectionDriver
{
    protected function setUpDriver(): void
    {
        $this->entityId = 0;
        $this->missingEntityId = 99;

        $collection = collect(array_values($this->getSeed()));

        $entityType = new EntityType('passenger');
        $entityType->setKey((new DeclaredProperty('id', Type::int64()))->addAnnotation(new ComputedDefaultValue));
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
        return array_values(Lodata::getEntitySet($this->entitySet)->getCollection()->toArray());
    }
}