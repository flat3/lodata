<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Helpers;

use Flat3\Lodata\Facades\Lodata;
use Illuminate\Redis\Connections\Connection;

trait UseRedisAssertions
{
    /** @var array $redisSnapshot */
    protected $redisSnapshot;

    protected function captureRedisState()
    {
        $this->redisSnapshot = $this->snapshotRedis();
    }

    protected function snapshotRedis(): array
    {
        $data = [];

        /** @var Connection $redis */
        $redis = Lodata::getEntitySet($this->entitySet)->getConnection();
        foreach ($redis->keys('*') as $key) {
            $data[$key] = unserialize($redis->get($key));
        }

        return $data;
    }

    protected function assertRedisUnchanged()
    {
        $this->assertEquals($this->redisSnapshot, $this->snapshotRedis());
    }

    protected function assertRedisDiffSnapshot()
    {
        $driver = new StreamingJsonDriver;

        $this->assertDiffSnapshot(
            $driver->serialize($this->redisSnapshot),
            $driver->serialize($this->snapshotRedis())
        );
    }
}