<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Drivers;

use Flat3\Lodata\Drivers\FilesystemEntitySet;
use Flat3\Lodata\Drivers\FilesystemEntityType;
use Flat3\Lodata\Facades\Lodata;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

trait WithFilesystemDriver
{
    protected function setUpDriver(): void
    {
        $this->entitySet = 'disk';
        $this->entityId = 'a1.txt';
        $this->missingEntityId = 'qq.txt';
        $this->entitySetKey = 'path';

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('testing');
        $disk->put('a1.txt', 'hello');
        $disk->put('d1/a1.txt', 'hello1');

        $entityType = new FilesystemEntityType();
        $entitySet = new FilesystemEntitySet('disk', $entityType);
        $entitySet->setDisk($disk);

        Lodata::add($entitySet);
        $this->updateETag();
    }

    protected function assertFile(string $path)
    {
        $this->assertMatchesSnapshot(Storage::disk('testing')->get($path));
    }

    protected function withModifiedPropertySourceName()
    {
        $passengerSet = Lodata::getEntitySet($this->entitySet);
        $ageProperty = $passengerSet->getType()->getProperty('timestamp');
        $ageProperty->setName('ttimestamp');
        $passengerSet->getType()->getProperties()->reKey();
        $passengerSet->setPropertySourceName($ageProperty, 'timestamp');
    }
}
