<?php

namespace Flat3\OData\Tests\Unit\Queries\Types;

use Flat3\OData\Tests\Data\FlightDataModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class StringTest extends TestCase
{
    use FlightDataModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightDataModel();
    }

    public function test_filter_string_eq()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights')
                ->query('$filter', "origin eq 'lhr'")
        );
    }

    public function test_filter_string_ne()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights')
                ->query('$filter', "origin ne 'lhr'")
        );
    }
}

