<?php

namespace Flat3\Lodata\Tests\Unit\Queries\Entity;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\DynamicProperty;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\PrimitiveType;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type\Int32;
use Flat3\Lodata\Type\String_;

class EntityTest extends TestCase
{
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

    public function test_read_a_qualified_entity()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/com.example.odata.flights(1)')
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

    public function test_dynamic_property()
    {
        /** @var EntityType $airport */
        $airport = Lodata::getEntityType('airport');

        $property = new class('cp', PrimitiveType::int32()) extends DynamicProperty {
            public function invoke(Entity $entity, Transaction $transaction)
            {
                return new Int32(4);
            }
        };

        $airport->addProperty($property);
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports(1)')
        );
    }

    public function test_bad_dynamic_property()
    {
        /** @var EntityType $airport */
        $airport = Lodata::getEntityType('airport');

        $property = new class('cp', PrimitiveType::int32()) extends DynamicProperty {
            public function invoke(Entity $entity, Transaction $transaction)
            {
                return new String_(4);
            }
        };

        $airport->addProperty($property);

        ob_start();

        $this->assertTextMetadataResponse(
            Request::factory()
                ->path('/airports(1)'));

        $this->assertMatchesSnapshot(ob_get_clean());
    }
}
