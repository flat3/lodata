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
        $this->etag = 'W/"88cde431860aba3660b32d5d00ec3c0043ddc0c03c240c988f3555dbd62bf267"';

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('testing');
        $disk->put('a1.txt', 'hello');
        $disk->put('d1/a1.txt', 'hello1');

        $entityType = new FilesystemEntityType();
        $entitySet = new FilesystemEntitySet('disk', $entityType);
        $entitySet->setDisk($disk);

        Lodata::add($entitySet);
    }
}