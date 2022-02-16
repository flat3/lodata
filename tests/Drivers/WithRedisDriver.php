<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Drivers;

use Flat3\Lodata\Drivers\RedisEntitySet;
use Flat3\Lodata\Drivers\RedisEntityType;
use Flat3\Lodata\Facades\Lodata;
use Illuminate\Support\Facades\Redis;

trait WithRedisDriver
{
    protected function setUpDriver(): void
    {
        $this->entityId = 'alpha';
        $this->missingEntityId = 'missing';
        $this->etag = 'W/"b8ecab1508e1d5a3b34b38b20247e102bb76255257d60bcbd4708d772e275eef"';

        foreach ($this->getSeed() as $key => $record) {
            // @phpstan-ignore-next-line
            Redis::set($key, serialize($record));
        }

        $entityType = new RedisEntityType('passenger');
        $this->addPassengerProperties($entityType);
        $entitySet = new RedisEntitySet($this->entitySet, $entityType);
        Lodata::add($entitySet);
        $this->captureRedisState();
    }

    protected function tearDownDriver(): void
    {
        $this->assertRedisDiffSnapshot();
    }

    protected function assertRedisRecord($key): void
    {
        // @phpstan-ignore-next-line
        $this->assertMatchesObjectSnapshot(unserialize(Redis::get($key)));
    }
}