<?php

namespace Flat3\Lodata\Tests\Unit\Queries\Types;

use Flat3\Lodata\Tests\Request;

class DateTimeTest extends TypeTest
{
    public function test_filter_datetime_eq()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->filter("sam_datetime eq 2001-11-10T14:00:00Z+00:00")
                ->select('id,sam_datetime')
        );
    }

    public function test_filter_datetime_gt()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->filter("sam_datetime gt 2001-11-10T14:00:00Z+00:00")
                ->select('id,sam_datetime')
        );
    }

    public function test_filter_datetime_lt()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->filter("sam_datetime lt 2001-01-01T00:00:00Z+00:00")
                ->select('id,sam_datetime')
        );
    }
}
