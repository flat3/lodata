<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Count;

use Flat3\Lodata\Tests\Drivers\WithRedisDriver;

/**
 * @group redis
 */
class RedisTest extends Count
{
    use WithRedisDriver;

    public function test_count_ignores_skip()
    {
        $this->expectNotToPerformAssertions();
    }

    public function test_count_path_ignores_skip()
    {
        $this->expectNotToPerformAssertions();
    }
}
