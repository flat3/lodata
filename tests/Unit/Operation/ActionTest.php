<?php

namespace Flat3\OData\Tests\Unit\Operation;

use Flat3\OData\Tests\Data\FlightModel;
use Flat3\OData\Tests\Data\TextModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class ActionTest extends TestCase
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
                ->path('/exa1()')
        );
    }

    public function test_callback_entity()
    {
        $this->assertNotFound(
            Request::factory()
                ->path('/exa2()')
        );
    }
}