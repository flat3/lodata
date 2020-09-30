<?php

namespace Flat3\OData\Tests\Unit\Options;

use Flat3\OData\Tests\Data\FlightDataModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class SkipTest extends TestCase
{
    use FlightDataModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightDataModel();
    }

    public function test_skip()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$skip', '1')
        );
    }

    public function test_top_skip()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$top', '1')
                ->query('$skip', '1')
        );
    }

    public function test_skip_two()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$skip', '2')
        );
    }

    public function test_skip_many()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$skip', '999')
        );
    }

    public function test_skip_invalid_type()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/airports')
                ->query('$skip', 'xyz')
        );
    }

    public function test_skip_invalid_negative()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/airports')
                ->query('$skip', '-2')
        );
    }
}

