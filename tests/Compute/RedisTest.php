<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Compute;

use Flat3\Lodata\Tests\Drivers\WithRedisDriver;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group redis
 */
#[Group('redis')]
class RedisTest extends Compute
{
    use WithRedisDriver;
}
