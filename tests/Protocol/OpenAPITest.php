<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

class OpenAPITest extends TestCase
{
    use WithNumericCollectionDriver;

    public function test_has_flight_openapi_document()
    {
        $this->withMathFunctions();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/openapi.json')
        );
    }

    public function test_error_openapi_document_not_root()
    {
        $this->assertBadRequest(
            (new Request)
                ->path('/passengers/openapi.json')
        );
    }

    public function test_document_url()
    {
        $this->assertEquals('http://localhost/odata/openapi.json', Lodata::getOpenApiUrl());
    }
}
