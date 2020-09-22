<?php

namespace Flat3\OData\Tests\Unit\Queries;

use Flat3\OData\Tests\Models\Flight;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class EntitySetTest extends TestCase
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
                ->path('/flights')
        );
    }

    public function test_uses_maxpagesize_preference()
    {
        (new Flight([]))->save();
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights')
                ->header('Prefer', 'maxpagesize=1')
        );
    }
}
