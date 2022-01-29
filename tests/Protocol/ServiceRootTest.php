<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

class ServiceRootTest extends TestCase
{
    use WithNumericCollectionDriver;

    public function test_has_flight_service_document_at_service_root()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
        );
    }

    public function test_has_flight_service_document_with_modified_title_at_service_root()
    {
        $passengers = Lodata::getEntitySet('passengers');
        $passengers->setTitle('Passengers');

        $this->assertJsonMetadataResponse(
            (new Request)
        );
    }
}
