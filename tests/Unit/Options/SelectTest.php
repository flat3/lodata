<?php

namespace Flat3\Lodata\Tests\Unit\Options;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class SelectTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_selects_set()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights')
                ->query('$select', 'origin,destination')
        );
    }

    public function test_selects_singular()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->query('$select', 'origin,destination')
        );
    }

    public function test_selects_invalid()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/flights(1)')
                ->query('$select', 'invalid')
        );
    }
}