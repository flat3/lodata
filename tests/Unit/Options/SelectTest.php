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
            (new Request)
                ->path('/flights')
                ->query('$select', 'origin,destination')
        );
    }

    public function test_selects_singular()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/flights(1)')
                ->query('$select', 'origin,destination')
        );
    }

    public function test_selects_invalid()
    {
        $this->assertBadRequest(
            (new Request)
                ->path('/flights(1)')
                ->query('$select', 'invalid')
        );
    }
}