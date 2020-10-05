<?php

namespace Flat3\OData\Tests\Unit\Queries\Entity;

use Flat3\OData\Tests\Data\FlightModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class EntityTest extends TestCase
{
    use FlightModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_read_an_entity()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
        );
    }

    public function test_read_an_entity_with_referenced_key()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(id=@id)')
                ->query('@id', 1)
        );
    }

    public function test_read_an_entity_with_invalid_key()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path("/flights(origin='lax')")
        );
    }

    public function test_read_an_entity_with_invalid_referenced_key()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/flights(origin=@origin)')
                ->query('@origin', 'lax')
        );
    }

    public function test_not_found()
    {
        $this->assertNotFound(
            Request::factory()
                ->path('/flights(99)')
        );
    }

    public function test_read_with_select()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->query('$select', 'destination')
        );
    }

    public function test_read_with_multiple_select()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->query('$select', 'destination,origin')
        );
    }

    public function test_rejects_invalid_select()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/flights(1)')
                ->query('$select', 'nonexistent')
        );
    }

    public function test_empty_select_ignored()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->query('$select', '')
        );
    }

    public function test_select_star()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->query('$select', '*')
        );
    }

    public function test_expand()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->query('$expand', 'airports')
        );
    }

    public function test_expand_select()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->query('$expand', 'airports')
                ->query('$select', 'origin')
        );
    }
}
