<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Filter;

use Flat3\Lodata\Tests\Drivers\WithMongoDriver;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group mongo
 */
#[Group('mongo')]
class MongoTest extends Database
{
    use WithMongoDriver;

    public function test_filter_has()
    {
        $this->markTestSkipped();
    }

    public function test_filter_has_multi()
    {
        $this->markTestSkipped();
    }

    public function test_path_query_filter_search()
    {
        $this->markTestSkipped();
    }

    public function test_filter_navigation_count_eq()
    {
        $this->markTestSkipped();
    }

    public function test_filter_navigation_count_gt()
    {
        $this->markTestSkipped();
    }

    public function test_filter_navigation_count_combined()
    {
        $this->markTestSkipped();
    }
}
