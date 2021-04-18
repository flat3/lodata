<?php

namespace Flat3\Lodata\Tests\Unit\Protocol;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class ServiceMetadataTest extends TestCase
{
    public function test_has_empty_metadata_document_at_document_root()
    {
        $this->assertMetadataDocuments();
    }

    public function test_has_flight_metadata_document_at_document_root()
    {
        $this->withFlightModel();
        $this->withMathFunctions();

        $this->assertMetadataDocuments();
    }

    public function test_error_service_metadata_not_service_root()
    {
        $this->withFlightModel();

        $this->assertBadRequest(
            Request::factory()
                ->xml()
                ->path('/flights/$metadata')
        );
    }
}
