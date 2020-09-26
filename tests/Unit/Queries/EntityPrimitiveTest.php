<?php

namespace Flat3\OData\Tests\Unit\Queries;

use Flat3\OData\Tests\Data\FlightDataModel;
use Flat3\OData\Tests\Models\Flight;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class EntityPrimitiveTest extends TestCase
{
    use FlightDataModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightDataModel();
    }

    public function test_read_an_entity_set_primitive()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)/id')
        );
    }

    public function test_null_no_content()
    {
        $flight = (new Flight([
            'origin' => null,
        ]));
        $flight->save();

        $this->assertNoContent(
            Request::factory()
                ->path('/flights('.$flight->id.')/origin')
        );
    }

}
