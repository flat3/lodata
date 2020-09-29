<?php

namespace Flat3\OData\Tests\Unit\Protocol;

use Flat3\OData\Tests\Data\FlightDataModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class ParameterAliasTest extends TestCase
{
    use FlightDataModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightDataModel();
    }

    public function test_alias()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->filter("code eq @code")
                ->query('@code', "'sfo'")
        );
    }

    public function test_alias_date()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->filter("construction_date eq @code")
                ->query('@code', '1946-03-25')
        );
    }

    public function test_alias_bool()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->filter("is_big eq @code")
                ->query('@code', 'true')
        );
    }

    public function test_nonexistent_alias()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/airports')
                ->filter("code eq @code")
        );
    }
}
