<?php

namespace Flat3\Lodata\Tests\Unit\Queries\EntitySet\Filter;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class QueryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_compound_filter()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->filter("startswith(code, 'l') and is_big eq true")
        );
    }
}
