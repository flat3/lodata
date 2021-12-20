<?php

namespace Flat3\Lodata\Tests\Unit\Options;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class TopTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_top()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->top(2)
        );
    }

    public function test_top_one()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->top(1)
        );
    }

    public function test_top_many()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->top(999)
        );
    }

    public function test_top_invalid_type()
    {
        $this->assertBadRequest(
            (new Request)
                ->path('/airports')
                ->top('xyz')
        );
    }

    public function test_top_invalid_negative()
    {
        $this->assertBadRequest(
            (new Request)
                ->path('/airports')
                ->top(-2)
        );
    }

    public function test_page()
    {
        $page = $this->getResponseBody(
            $this->assertJsonResponse(
                (new Request)
                    ->path('/airports')
                    ->top(2)
            )
        );

        $this->assertJsonResponse(
            $this->urlToReq($page->{'@nextLink'})
        );
    }

    public function test_page_select()
    {
        $page = $this->getResponseBody(
            $this->assertJsonResponse(
                (new Request)
                    ->path('/airports')
                    ->top(2)
                    ->select('code')
            )
        );

        $this->assertJsonResponse(
            $this->urlToReq($page->{'@nextLink'})
        );
    }
}

