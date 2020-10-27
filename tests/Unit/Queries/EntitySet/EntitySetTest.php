<?php

namespace Flat3\Lodata\Tests\Unit\Queries\EntitySet;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\Metadata;

class EntitySetTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_read_an_entity_set()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights')
        );
    }

    public function test_read_an_entity_with_full_metadata()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->metadata(Metadata\Full::name)
                ->path('/flights')
        );
    }

    public function test_read_an_entity_with_no_metadata()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->metadata(Metadata\None::name)
                ->path('/flights')
        );
    }
}
