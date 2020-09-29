<?php

namespace Flat3\OData\Tests\Unit\Options;

use Flat3\OData\Tests\Data\FlightDataModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class SelectTest extends TestCase
{
    use FlightDataModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightDataModel();
    }

    public function test_selects_set()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights')
                ->query('$select', 'origin,destination')
        );
    }

    public function test_selects_singular()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->query('$select', 'origin,destination')
        );
    }

    public function test_selects_invalid()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/flights(1)')
                ->query('$select', 'invalid')
        );
    }
}