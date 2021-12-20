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
                ->count('true')
        );
    }

    public function test_count_ignores_top()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->top(1)
                ->count('true')
        );
    }

    public function test_count_ignores_skip()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->skip(1)
                ->count('true')
        );
    }

    public function test_count_uses_filter()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->count('true')
                ->filter('is_big eq false')
        );
    }

    public function test_count_uses_search()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->count('true')
                ->search('sfo')
        );
    }

    public function test_count_false()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->count('false')
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

