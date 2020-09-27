<?php

namespace Flat3\OData\Tests\Unit\Protocol;

use Flat3\OData\Tests\Data\FlightDataModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class IEEE754 extends TestCase
{
    use FlightDataModel;

    public function test_ieee754_response()
    {
        $this->withFlightDataModel();

        $this->assertJsonMetadataResponse(
            Request::factory()
                ->header('accept', 'application/json;IEEE754Compatible=true')
                ->path('/flights(1)')
        );
    }

    public function test_no_ieee754_response()
    {
        $this->withFlightDataModel();

        $this->assertJsonMetadataResponse(
            Request::factory()
                ->header('accept', 'application/json;IEEE754Compatible=false')
                ->path('/flights(1)')
        );
    }
}