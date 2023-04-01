<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\EntityUpdate;

use Flat3\Lodata\Tests\Drivers\WithRedisDriver;
use Illuminate\Support\Facades\Redis;

/**
 * @group redis
 */
class RedisTest extends EntityUpdate
{
    use WithRedisDriver;

    public function test_update()
    {
        parent::test_update();

        $this->assertRedisRecord($this->entityId);
    }

    public function test_delete()
    {
        parent::test_delete();

        // @phpstan-ignore-next-line
        $this->assertNull(Redis::get($this->entityId));
    }
}
