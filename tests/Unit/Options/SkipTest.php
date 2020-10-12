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
            Request::factory()
                ->path('/airports')
                ->query('$skip', '1')
        );
    }

    public function test_top_skip()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$top', '1')
                ->query('$skip', '1')
        );
    }

    public function test_skip_two()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$skip', '2')
        );
    }

    public function test_skip_many()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$skip', '999')
        );
    }

    public function test_skip_invalid_type()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/airports')
                ->query('$skip', 'xyz')
        );
    }

    public function test_skip_invalid_negative()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/airports')
                ->query('$skip', '-2')
        );
    }
}

