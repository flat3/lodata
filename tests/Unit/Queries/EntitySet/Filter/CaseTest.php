<?php

namespace Flat3\Lodata\Tests\Unit\Queries\EntitySet\Filter;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class CaseTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_lower()
    {
        $this->assertJsonResponse(
            (new Request)
                ->filter("code eq 'lax'")
                ->path('/airports')
        );
    }

    public function test_upper()
    {
        $this->assertJsonResponse(
            (new Request)
                ->filter("code EQ 'lax'")
                ->path('/airports')
        );
    }
}
