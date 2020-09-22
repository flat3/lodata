<?php

namespace Flat3\OData\Tests\Unit\Queries;

use Flat3\OData\Tests\Models\Flight;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class EntityPrimitiveRawTest extends TestCase
{
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
                ->path('/flights('.$flight->id.')/origin/$value')
        );
    }
}
