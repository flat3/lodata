<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Entity;

use Flat3\Lodata\Tests\Drivers\WithRedisDriver;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group redis
 */
#[Group('redis')]
class RedisTest extends Entity
{
    use WithRedisDriver;

    public function test_read_alternative_key()
    {
        $this->expectNotToPerformAssertions();
    }
}
