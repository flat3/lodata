<?php

namespace Flat3\OData\Tests\Unit\Queries\EntitySet;

use Flat3\OData\Tests\Data\FlightDataModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class CountTest extends TestCase
{
    use FlightDataModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightDataModel();
    }

    public function test_count()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$count', 'true')
        );
    }

    public function test_count_ignores_top()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$top', '1')
                ->query('$count', 'true')
        );
    }

    public function test_count_ignores_skip()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$skip', '1')
                ->query('$count', 'true')
        );
    }

    public function test_count_uses_filter()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$count', 'true')
                ->query('$filter', 'is_big eq false')
        );
    }

    public function test_count_uses_search()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$count', 'true')
                ->query('$search', 'sfo')
        );
    }

    public function test_count_false()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$count', 'false')
        );
    }

    public function test_count_invalid()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/airports')
                ->query('$count', 'invalid')
        );
    }
}

