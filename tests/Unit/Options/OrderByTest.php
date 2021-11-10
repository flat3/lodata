<?php

namespace Flat3\Lodata\Tests\Unit\Options;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class OrderByTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_orderby_desc()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->query('$orderby', 'id desc')
        );
    }

    public function test_orderby_asc()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->query('$orderby', 'code asc')
        );
    }

    public function test_orderby_default_asc()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->query('$orderby', 'code')
        );
    }

    public function test_orderby_invalid()
    {
        $this->assertBadRequest(
            (new Request)
                ->path('/flights')
                ->query('$orderby', 'origin wrong')
        );
    }

    public function test_orderby_invalid_property()
    {
        $this->assertBadRequest(
            (new Request)
                ->path('/flights')
                ->query('$orderby', 'invalid asc')
        );
    }

    public function test_orderby_multiple()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->query('$orderby', 'id desc, code asc')
        );
    }

    public function test_orderby_invalid_multiple()
    {
        $this->assertBadRequest(
            (new Request)
                ->path('/flights')
                ->query('$orderby', 'origin asc id desc')
        );
    }
}
