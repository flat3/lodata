<?php

namespace Flat3\OData\Tests\Unit\Queries;

use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class EntitySetTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightDataModel();
    }

    public function test_read_an_entity_set()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights')
        );
    }

    public function test_uses_maxpagesize_preference()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights')
                ->header('Prefer', 'maxpagesize=1')
        );
    }

    public function test_uses_odata_maxpagesize_preference()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights')
                ->header('Prefer', 'odata.maxpagesize=1')
        );
    }

    public function test_selects()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights')
                ->query('$select', 'origin')
        );
    }

    public function test_filter_eq()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights')
                ->query('$filter', "origin eq 'lhr'")
        );
    }
}
