<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Count;

use Flat3\Lodata\Tests\Drivers\WithRedisDriver;

class RedisTest extends CountTest
{
    use WithRedisDriver;

    public function test_count_ignores_skip()
    {
    }

    public function test_count_path_ignores_skip()
    {
    }
}