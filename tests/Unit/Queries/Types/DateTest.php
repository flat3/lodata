<?php

namespace Flat3\Lodata\Tests\Unit\Queries\Types;

use Flat3\Lodata\Tests\Request;

class DateTest extends TypeTest
{
    public function test_filter_date_eq()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->filter('construction_date eq 1946-03-25')
                ->select('id,construction_date')
        );
    }

    public function test_filter_date_gt()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->filter('construction_date gt 1935-01-01')
                ->select('id,construction_date')
        );
    }

    public function test_filter_date_lt()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->filter('construction_date lt 1935-01-01')
                ->select('id,construction_date')
        );
    }

    public function test_filter_invalid_date()
    {
        $this->assertBadRequest(
            (new Request)
                ->path('/airports')
                ->filter('construction_date lt 1935-0x-')
        );
    }
}
