<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Helpers;

use Flat3\Lodata\Facades\Lodata;

trait UseCollectionAssertions
{
    /** @var array $collectionSnapshot */
    protected $collectionSnapshot;

    protected function captureCollectionState()
    {
        $this->collectionSnapshot = $this->snapshotCollection();
    }

    protected function snapshotCollection(): array
    {
        return Lodata::getEntitySet($this->entitySet)->getCollection()->toArray();
    }

    protected function assertCollectionUnchanged()
    {
        $this->assertEquals($this->collectionSnapshot, $this->snapshotCollection());
    }

    protected function assertCollectionDiffSnapshot()
    {
        $driver = new StreamingJsonDriver;

        $this->assertDiffSnapshot(
            $driver->serialize($this->collectionSnapshot),
            $driver->serialize($this->snapshotCollection())
        );
    }
}