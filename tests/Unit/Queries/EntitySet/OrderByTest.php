<?php

namespace Flat3\OData\Tests\Unit\Queries\EntitySet;

use Flat3\OData\Tests\Data\FlightDataModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class OrderByTest extends TestCase
{
    use FlightDataModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightDataModel();
    }

    public function test_orderby_desc()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$orderby', 'id desc')
        );
    }

    public function test_orderby_asc()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$orderby', 'code asc')
        );
    }

    public function test_orderby_default_asc()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$orderby', 'code')
        );
    }

    public function test_orderby_invalid()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/flights')
                ->query('$orderby', 'origin wrong')
        );
    }

    public function test_orderby_invalid_property()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/flights')
                ->query('$orderby', 'invalid asc')
        );
    }

    public function test_orderby_multiple()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$orderby', 'id desc, code asc')
        );
    }

    public function test_orderby_invalid_multiple()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/flights')
                ->query('$orderby', 'origin asc id desc')
        );
    }
}
