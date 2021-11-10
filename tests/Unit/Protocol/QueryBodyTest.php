<?php

namespace Flat3\Lodata\Tests\Unit\Protocol;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class QueryBodyTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->withFlightModel();
    }

    public function test_wrong_content_type()
    {
        $this->assertNotAcceptable(
            (new Request)
                ->path('flights/$query')
                ->post()
        );
    }

    public function test_wrong_method()
    {
        $this->assertMethodNotAllowed(
            (new Request)
                ->text()
                ->path('flights/$query')
        );
    }

    public function test_query()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('flights/$query')
                ->post()
                ->text()
                ->body(http_build_query([
                    '$count' => 'true',
                    '$filter' => "destination eq 'lax'",
                ]))
        );
    }
}