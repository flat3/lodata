<?php

namespace Flat3\OData\Tests\Unit\Options;

use Flat3\OData\Tests\Data\FlightModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class TopTest extends TestCase
{
    use FlightModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
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

    public function test_page_select()
    {
        $page = $this->jsonResponse(
            $this->assertJsonResponse(
                Request::factory()
                    ->path('/airports')
                    ->query('$top', '2')
                    ->query('$select', 'code')
            )
        );

        $this->assertJsonResponse(
            $this->urlToReq($page->{'@nextLink'})
        );
    }
}

