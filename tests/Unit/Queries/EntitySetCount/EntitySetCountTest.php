<?php

namespace Flat3\Lodata\Tests\Unit\Queries\EntitySetCount;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class EntitySetCountTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_count()
    {
        $this->assertTextMetadataResponse(
            (new Request)
                ->text()
                ->path('/flights/$count')
        );
    }

    public function test_count_ignores_top()
    {
        $this->assertTextResponse(
            (new Request)
                ->path('/airports/$count')
                ->text()
                ->top(1)
        );
    }

    public function test_count_ignores_skip()
    {
        $this->assertTextResponse(
            (new Request)
                ->path('/airports/$count')
                ->text()
                ->skip(1)
        );
    }

    public function test_count_uses_filter()
    {
        $this->assertTextResponse(
            (new Request)
                ->path('/airports/$count')
                ->text()
                ->filter('is_big eq false')
        );
    }

    public function test_count_uses_search()
    {
        $this->assertTextResponse(
            (new Request)
                ->path('/airports/$count')
                ->text()
                ->search('sfo')
        );
    }

    public function test_count_navigation_property()
    {
        $this->assertTextResponse(
            (new Request)
                ->path('/flights(1)/passengers/$count')
                ->text()
        );
    }
}
