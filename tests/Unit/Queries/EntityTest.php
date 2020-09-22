<?php

namespace Flat3\OData\Tests\Unit\Queries;

use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class EntityTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightDataModel();
    }

    public function test_read_an_entity_set()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
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
}
