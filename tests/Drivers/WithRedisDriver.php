<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Drivers;

use Flat3\Lodata\Drivers\RedisEntitySet;
use Flat3\Lodata\Drivers\RedisEntityType;
use Flat3\Lodata\Facades\Lodata;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;

trait WithRedisDriver
{
    protected function setUpDriver(): void
    {
        $this->entityId = 'alpha';
        $this->missingEntityId = 'missing';
        $this->entitySetKey = 'key';

        foreach ($this->getSeed() as $key => $record) {
            // @phpstan-ignore-next-line
            Redis::set($key, serialize($record));
        }

        $entityType = new RedisEntityType('passenger');
        $this->addPassengerProperties($entityType);
        $entitySet = new RedisEntitySet($this->entitySet, $entityType);
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
        $data = [];

        /** @var Connection $redis */
        $redis = Lodata::getEntitySet($this->entitySet)->getConnection();

        foreach ($redis->keys('*') as $key) {
            $data[$key] = unserialize($redis->get($key));
        }

        return $data;
    }

    protected function assertRedisRecord($key): void
    {
        // @phpstan-ignore-next-line
        $this->assertMatchesObjectSnapshot(unserialize(Redis::get($key)));
    }
}
