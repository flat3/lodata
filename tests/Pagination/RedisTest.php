<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Pagination;

use Flat3\Lodata\Tests\Drivers\WithRedisDriver;

class RedisTest extends PaginationTest
{
    use WithRedisDriver;
}