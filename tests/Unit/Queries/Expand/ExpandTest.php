<?php

namespace Flat3\Lodata\Tests\Unit\Queries\Expand;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class ExpandTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_expand()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights')
                ->query('$expand', 'passengers')
        );
    }

    public function test_expand_property()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)/passengers')
        );
    }

    public function test_expand_containing_select()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->query('$expand', 'passengers($select=name)')
        );
    }

    public function test_expand_select()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->query('$expand', 'passengers')
                ->query('$select', 'origin')
        );
    }

    public function test_select_within_expand()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->query('$select', 'origin,destination')
                ->query('$expand', 'passengers($select=name)')
        );
    }
}
