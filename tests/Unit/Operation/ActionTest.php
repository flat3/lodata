<?php

namespace Flat3\OData\Tests\Unit\Operation;

use Flat3\OData\Tests\Data\FlightDataModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class ActionTest extends TestCase
{
    use FlightDataModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightDataModel();
    }

    public function test_callback()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/exa1()')
        );
    }

    public function test_callback_entity()
    {
        $this->markTestIncomplete();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/exa2()')
        );
    }

    public function test_callback_entity_set()
    {
        $this->markTestIncomplete();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/exa3()')
        );
    }
}