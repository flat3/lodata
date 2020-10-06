<?php

namespace Flat3\OData\Tests\Unit\Operation;

use Flat3\OData\Tests\Data\FlightModel;
use Flat3\OData\Tests\Data\TextModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class FunctionTest extends TestCase
{
    use FlightModel;
    use TextModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
        $this->withTextModel();
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
        $this->assertJsonResponse(
            Request::factory()
                ->path("/exf3(code='xyz')")
        );
    }

    public function test_callback_entity_set()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/textf1()')
        );
    }

    public function test_with_arguments()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/add(a=3,b=4)')
        );
    }

    public function test_with_argument_order()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/div(a=3,b=4)')
        );

        $this->assertJsonResponse(
            Request::factory()
                ->path('/div(b=3,a=4)')
        );
    }

    public function test_with_indirect_arguments()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/add(a=@c,b=@d)')
                ->query('@c', 1)
                ->query('@d', 2)
        );
    }

    public function test_with_single_indirect_argument()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/add(a=@c,b=@c)')
                ->query('@c', 1)
        );
    }

    public function test_with_missing_indirect_arguments()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/add(a=@c,b=@e)')
                ->query('@c', 1)
                ->query('@d', 2)
        );
    }

    public function test_callback_modified_flight_entity_set()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/ffn1()')
        );
    }
}