<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Drivers;

use Flat3\Lodata\Drivers\CSVEntitySet;
use Flat3\Lodata\Drivers\CSVEntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\JSON;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use SplTempFileObject;

trait WithCSVDriver
{
    protected function setUpDriver(): void
    {
        $this->entityId = 0;
        $this->missingEntityId = 99;
        $this->entitySetKey = 'offset';

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('testing');

        $entityType = new CSVEntityType('passenger');
        $this->addPassengerProperties($entityType);
        $keys = array_fill_keys($entityType->getDeclaredProperties()->keys(), null);
        unset($keys['offset']);

        $csv = Writer::createFromFileObject(new SplTempFileObject());
        foreach ($this->getSeed() as $record) {
            foreach ($record as $key => &$value) {
                if (is_array($value)) {
                    $value = JSON::encode($value);
                }
            }

            $csv->insertOne(array_merge($keys, $record));
        }
        $disk->write('test.csv', $csv->toString());

        $entitySet = new CSVEntitySet($this->entitySet, $entityType);
        $entitySet->setDisk($disk);
        $entitySet->setFilePath('test.csv');
        Lodata::add($entitySet);
        $this->updateETag();
    }
}
