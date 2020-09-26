<?php

namespace Flat3\OData\Tests\Unit\Queries\Types;

use Flat3\OData\Tests\Request;

class DateTimeTest extends TypeTest
{
    public function test_filter_datetime_gt()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->filter("construction_date gt 1935-01-01")
                ->select('id,construction_date')
        );
    }

    public function test_filter_datetime_lt()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->filter("construction_date lt 1935-01-01")
                ->select('id,construction_date')
        );
    }

    public function test_filter_invalid_datetime()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/airports')
                ->filter("construction_date lt 1935-0x-")
        );
    }

    public function test_filter_time_eq()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->filter('open_time eq 08:00:00')
                ->select('id,open_time')
        );
    }

    public function test_filter_time_eq_2()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->filter('open_time gt 08:30:00')
                ->select('id,open_time')
        );
    }

    public function test_filter_time_eq_3()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->filter('open_time lt 07:13:13')
                ->select('id,open_time')
        );
    }
}
