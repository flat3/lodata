<?php

namespace Flat3\OData\Tests\Unit\Operation;

use Flat3\OData\Tests\Data\FlightDataModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class FunctionTest extends TestCase
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
                ->path('/exf1()')
        );
    }

    public function test_callback_entity()
    {
        $this->markTestIncomplete();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/exf2()')
        );
    }

    public function test_callback_entity_set()
    {
        $this->markTestIncomplete();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/example()')
        );
    }

    public function test_with_arguments()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/add(a=3,b=4)')
        );
    }
}