<?php

namespace Flat3\OData\Tests\Unit\Protocol;

use Flat3\OData\Tests\Data\FlightDataModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class ServiceRootTest extends TestCase
{
    use FlightDataModel;

    public function test_has_empty_service_document_at_service_root()
    {
        $this->assertJsonResponse(
            Request::factory()
        );
    }

    public function test_has_flight_service_document_at_service_root()
    {
        $this->withFlightDataModel();

        $this->assertJsonResponse(
            Request::factory()
        );
    }
}
