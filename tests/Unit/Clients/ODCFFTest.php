<?php

namespace Flat3\Lodata\Tests\Unit\Clients;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Data\TestModels;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class ODCFFTest extends TestCase
{
    use TestModels;

    public function test_odcff()
    {
        $this->withFlightModel();
        $this->assertHtmlResponse(
            Request::factory()
                ->path('/_lodata/airports.odc')
        );
    }

    public function test_odcff_missing()
    {
        $this->assertNotFound(
            Request::factory()
                ->path('/_lodata/missing.odc')
        );
    }

    public function test_odcff_url()
    {
        $this->assertEquals('http://localhost/odata/_lodata/Flights.odc', Lodata::getOdcUrl('Flights'));
    }
}