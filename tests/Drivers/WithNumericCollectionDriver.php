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
        $this->etag = 'W/"ebfc135d107351a03fcdc08f917af37891403e478938884f004d697f64d96646"';

        $collection = collect(array_values($this->getSeed()));

        $entityType = new EntityType('passenger');
        $entityType->setKey((new DeclaredProperty('id', Type::int64()))->addAnnotation(new ComputedDefaultValue));
        $this->addPassengerProperties($entityType);
        $entityType->getDeclaredProperty('name')->setSearchable();
        $entitySet = new CollectionEntitySet($this->entitySet, $entityType);
        $entitySet->setCollection($collection);

        Lodata::add($entitySet);
    }
}