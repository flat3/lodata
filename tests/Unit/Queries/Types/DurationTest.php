<?php

namespace Flat3\Lodata\Tests\Unit\Queries\Types;

use Flat3\Lodata\Tests\Request;

class DurationTest extends TypeTest
{
    public function test_filter_duration_eq()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights')
                ->filter('duration eq PT11H25M0S')
        );
    }

    public function test_filter_duration_gt()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights')
                ->filter('duration gt PT39M')
        );
    }

    public function test_filter_duration_lt()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights')
                ->filter('duration lt PT38M')
        );
    }
}
