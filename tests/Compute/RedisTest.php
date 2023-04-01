<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Compute;

use Flat3\Lodata\Tests\Drivers\WithRedisDriver;

/**
 * @group redis
 */
class RedisTest extends Compute
{
    use WithRedisDriver;
}
