<?php

namespace Flat3\OData\Tests\Unit\Operation;

use Flat3\OData\DataModel;
use Flat3\OData\Entity;
use Flat3\OData\EntitySet;
use Flat3\OData\Operation\Function_;
use Flat3\OData\Tests\Data\FlightDataModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;
use Flat3\OData\Type\EntityType;

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
}