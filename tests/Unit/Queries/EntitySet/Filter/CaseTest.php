<?php

namespace Flat3\OData\Tests\Unit\Queries\EntitySet\Filter;

use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

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
            Request::factory()
                ->filter("code eq 'lax'")
                ->path('/airports')
        );
    }

    public function test_upper()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->filter("code EQ 'lax'")
                ->path('/airports')
        );
    }
}
