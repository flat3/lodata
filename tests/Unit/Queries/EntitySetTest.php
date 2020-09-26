<?php

namespace Flat3\OData\Tests\Unit\Queries;

use Flat3\OData\Tests\Data\FlightDataModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class EntitySetTest extends TestCase
{
    use FlightDataModel;

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

    public function test_filter_string_eq()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights')
                ->query('$filter', "origin eq 'lhr'")
        );
    }

    public function test_filter_string_ne()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights')
                ->query('$filter', "origin ne 'lhr'")
        );
    }

    public function test_filter_datetime_gt()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$filter', "construction_date gt 1935-01-01")
        );
    }

    public function test_filter_datetime_lt()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$filter', "construction_date lt 1935-01-01")
        );
    }

    public function test_filter_invalid_datetime()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/airports')
                ->query('$filter', "construction_date lt 1935-0x-")
        );
    }

    public function test_filter_time_eq()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$filter', 'open_time eq 08:00:00')
        );
    }

    public function test_filter_time_eq_2()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$filter', 'open_time gt 08:30:00')
        );
    }

    public function test_filter_time_eq_3()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$filter', 'open_time lt 07:13:13')
        );
    }
}
