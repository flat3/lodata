<?php

namespace Flat3\Lodata\Tests\Unit\Queries\EntityPrimitive;

use Flat3\Lodata\Tests\Models\Flight;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class EntityPrimitiveTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_read_an_entity_set_primitive()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)/id')
        );
    }

    public function test_delete_an_entity_set_primitive()
    {
        $this->assertNoContent(
            Request::factory()
                ->delete()
                ->path('/flights(1)/origin')
        );

        $this->assertDatabaseSnapshot();
    }

    public function test_update_an_entity_set_primitive()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)/origin')
                ->patch()
                ->body('sfo')
        );
    }

    public function test_null_no_content()
    {
        $flight = (new Flight([
            'origin' => null,
        ]));
        $flight->save();

        $this->assertNoContent(
            Request::factory()
                ->path('/flights('.$flight->id.')/origin')
        );
    }

}
