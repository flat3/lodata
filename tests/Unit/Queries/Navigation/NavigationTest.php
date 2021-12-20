<?php

namespace Flat3\Lodata\Tests\Unit\Queries\Navigation;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class NavigationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_apply_query_parameters_to_last_segment()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/flights(1)/passengers')
                ->select('flight_id,name')
                ->top(2)
        );
    }
}
