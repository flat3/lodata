<?php

namespace Flat3\Lodata\Tests\Unit\Options;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class CountTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_count()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->query('$count', 'true')
        );
    }

    public function test_count_ignores_top()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->query('$top', '1')
                ->query('$count', 'true')
        );
    }

    public function test_count_ignores_skip()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->query('$skip', '1')
                ->query('$count', 'true')
        );
    }

    public function test_count_uses_filter()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->query('$count', 'true')
                ->query('$filter', 'is_big eq false')
        );
    }

    public function test_count_uses_search()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->query('$count', 'true')
                ->query('$search', 'sfo')
        );
    }

    public function test_count_false()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->query('$count', 'false')
        );
    }

    public function test_count_invalid()
    {
        $this->assertBadRequest(
            (new Request)
                ->path('/airports')
                ->query('$count', 'invalid')
        );
    }
}

