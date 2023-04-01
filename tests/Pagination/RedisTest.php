<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Pagination;

use Flat3\Lodata\Tests\Drivers\WithRedisDriver;

/**
 * @group redis
 */
class RedisTest extends Pagination
{
    use WithRedisDriver;

    public function test_skip()
    {
        $this->expectNotToPerformAssertions();
    }

    public function test_top_skip()
    {
        $this->expectNotToPerformAssertions();
    }

    public function test_sequence_skip()
    {
        $this->expectNotToPerformAssertions();
    }
}
