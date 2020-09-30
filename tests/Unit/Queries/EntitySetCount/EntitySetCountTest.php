<?php

namespace Flat3\OData\Tests\Unit\Queries\EntitySetCount;

use Flat3\OData\Tests\Data\FlightDataModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class EntitySetCountTest extends TestCase
{
    use FlightDataModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightDataModel();
    }

    public function test_count()
    {
        $this->assertTextMetadataResponse(
            Request::factory()
                ->text()
                ->path('/flights/$count')
        );
    }

    public function test_count_ignores_top()
    {
        $this->assertTextResponse(
            Request::factory()
                ->path('/airports/$count')
                ->text()
                ->query('$top', '1')
        );
    }

    public function test_count_ignores_skip()
    {
        $this->assertTextResponse(
            Request::factory()
                ->path('/airports/$count')
                ->text()
                ->query('$skip', '1')
        );
    }

    public function test_count_uses_filter()
    {
        $this->assertTextResponse(
            Request::factory()
                ->path('/airports/$count')
                ->text()
                ->query('$filter', 'is_big eq false')
        );
    }

    public function test_count_uses_search()
    {
        $this->assertTextResponse(
            Request::factory()
                ->path('/airports/$count')
                ->text()
                ->query('$search', 'sfo')
        );
    }
}
