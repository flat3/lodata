<?php

namespace Flat3\Lodata\Tests\Unit\Protocol;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class ServiceRootTest extends TestCase
{
    public function test_has_empty_service_document_at_service_root()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
        );
    }

    public function test_has_flight_service_document_at_service_root()
    {
        $this->withFlightModel();

        $this->assertJsonMetadataResponse(
            Request::factory()
        );
    }

    public function test_has_flight_service_document_with_modified_title_at_service_root()
    {
        $this->withFlightModel();

        $flights = Lodata::getEntitySet('flights');
        $flights->setTitle('Floots');

        $this->assertJsonMetadataResponse(
            Request::factory()
        );
    }
}
