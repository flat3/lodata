<?php

namespace Flat3\OData\Tests\Unit\Queries;

use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class EntitySetCountTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightDataModel();
    }

    public function test_read_an_entity_set()
    {
        $this->assertTextResponse(
            Request::factory()
                ->text()
                ->path('/flights/$count')
        );
    }
}
