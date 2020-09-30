<?php

namespace Flat3\OData\Tests\Unit\Queries\EntitySet;

use Flat3\OData\Tests\Data\FlightDataModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class TopTest extends TestCase
{
    use FlightDataModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightDataModel();
    }

    public function test_top()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$top', '2')
        );
    }

    public function test_top_one()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$top', '1')
        );
    }

    public function test_top_many()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$top', '999')
        );
    }

    public function test_top_invalid_type()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/airports')
                ->query('$top', 'xyz')
        );
    }

    public function test_top_invalid_negative()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/airports')
                ->query('$top', '-2')
        );
    }

    public function test_page()
    {
        $page = $this->jsonResponse(
            $this->assertJsonResponse(
                Request::factory()
                    ->path('/airports')
                    ->query('$top', '2')
            )
        );

        $this->assertJsonResponse(
            $this->urlToReq($page->{'@nextLink'})
        );
    }
}

