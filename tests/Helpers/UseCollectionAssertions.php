<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Helpers;

trait UseCollectionAssertions
{
    /** @var array $collectionSnapshot */
    protected $collectionSnapshot;

    protected function captureCollectionState()
    {
        $this->collectionSnapshot = $this->snapshotCollection();
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