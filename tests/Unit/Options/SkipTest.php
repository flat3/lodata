<?php

namespace Flat3\Lodata\Tests\Unit\Options;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class SkipTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_skip()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->skip(1)
        );
    }

    public function test_top_skip()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->top(1)
                ->skip(1)
        );
    }

    public function test_skip_two()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->skip(2)
        );
    }

    public function test_skip_many()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->skip(999)
        );
    }

    public function test_skip_invalid_type()
    {
        $this->assertBadRequest(
            (new Request)
                ->path('/airports')
                ->skip('xyz')
        );
    }

    public function test_skip_invalid_negative()
    {
        $this->assertBadRequest(
            (new Request)
                ->path('/airports')
                ->skip(-2)
        );
    }
}

