<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Entity;

use Flat3\Lodata\Tests\Drivers\WithRedisDriver;

class RedisTest extends EntityTest
{
    use WithRedisDriver;

    public function test_read_alternative_key()
    {
        $this->markTestSkipped();
    }
}