<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

class ServiceMetadataTest extends TestCase
{
    use WithNumericCollectionDriver;

    public function test_has_flight_metadata_document_at_document_root()
    {
        $this->withMathFunctions();
        $this->assertMetadataSnapshot();
    }

    public function test_error_service_metadata_not_service_root()
    {
        $this->assertBadRequest(
            (new Request)
                ->xml()
                ->path('/passengers/$metadata')
        );
    }
}
