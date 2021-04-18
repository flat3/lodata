<?php

namespace Flat3\Lodata\Tests\Unit\Protocol;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class OpenAPITest extends TestCase
{
    public function test_has_empty_openapi_document()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/openapi.json')
        );
    }

    public function test_has_flight_openapi_document()
    {
        $this->withFlightModel();
        $this->withMathFunctions();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/openapi.json')
        );
    }

    public function test_error_openapi_document_not_root()
    {
        $this->withFlightModel();

        $this->assertBadRequest(
            Request::factory()
                ->path('/flights/openapi.json')
        );
    }

    public function test_document_url()
    {
        $this->assertEquals('http://localhost/odata/openapi.json', Lodata::getOpenApiUrl());
    }
}
