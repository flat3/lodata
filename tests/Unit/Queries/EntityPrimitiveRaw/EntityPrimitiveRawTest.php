<?php

namespace Flat3\OData\Tests\Unit\Queries\EntityPrimitiveRaw;

use Flat3\OData\Tests\Data\FlightDataModel;
use Flat3\OData\Tests\Models\Flight;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class EntityPrimitiveRawTest extends TestCase
{
    use FlightDataModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightDataModel();
    }

    public function test_read_an_entity_set_primitive_raw()
    {
        $this->assertTextResponse(
            Request::factory()
                ->text()
                ->path('/flights(1)/id/$value')
        );
    }

    public function test_null_raw_not_found()
    {
        $flight = (new Flight([
            'origin' => null,
        ]));
        $flight->save();

        $this->assertNotFound(
            Request::factory()
                ->text()
                ->path('/flights(0)/origin/$value')
        );
    }

    public function test_null_raw_no_found_content()
    {
        $flight = (new Flight([
            'origin' => null,
        ]));
        $flight->save();

        $this->assertNoContent(
            Request::factory()
                ->text()
                ->path('/flights('.$flight->id.')/origin/$value')
        );
    }

    public function test_raw_custom_accept()
    {
        $this->assertMetadataResponse(
            Request::factory()
                ->header('accept', 'application/octet-stream')
                ->path('/flights(1)/id/$value')
        );
    }

    public function test_raw_custom_format()
    {
        $this->assertMetadataResponse(
            Request::factory()
                ->query('$format', 'application/octet-stream')
                ->path('/flights(1)/id/$value')
        );
    }
}
