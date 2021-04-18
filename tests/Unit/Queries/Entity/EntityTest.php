<?php

namespace Flat3\Lodata\Tests\Unit\Queries\Entity;

use Flat3\Lodata\Entity;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\GeneratedProperty;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MetadataType;
use Flat3\Lodata\Type;
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

    public function test_read_an_entity_key_as_segment()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights/1')
        );
    }

    public function test_read_an_entity_property_key_as_segment()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights/1/origin')
        );
    }

    public function test_read_an_entity_key_as_segment_not_found()
    {
        $this->assertNotFound(
            Request::factory()
                ->path('/flights/99')
        );
    }

    public function test_read_an_entity_etag()
    {
        $this->assertMetadataResponse(
            Request::factory()
                ->path('/flights(1)')
        );
    }

    public function test_read_an_entity_if_match()
    {
        $this->assertMetadataResponse(
            Request::factory()
                ->header('if-match', 'W/"2ccaaf443e26494dff243377cb72fb508b6dfad077dd4216f294be3fc0e7d0b5"')
                ->path('/flights(1)')
        );
    }

    public function test_read_an_entity_if_match_failed()
    {
        $this->assertPreconditionFailed(
            Request::factory()
                ->header('if-match', 'xxx')
                ->path('/flights(1)')
        );
    }

    public function test_read_an_entity_if_match_any()
    {
        $this->assertMetadataResponse(
            Request::factory()
                ->header('if-match', '*')
                ->path('/flights(1)')
        );
    }

    public function test_read_an_entity_if_none_match_any()
    {
        $this->assertMetadataResponse(
            Request::factory()
                ->header('if-none-match', '*')
                ->path('/flights(1)')
        );
    }

    public function test_read_an_entity_if_none_match()
    {
        $this->assertNotModified(
            Request::factory()
                ->header('if-none-match', 'W/"2ccaaf443e26494dff243377cb72fb508b6dfad077dd4216f294be3fc0e7d0b5"')
                ->path('/flights(1)')
        );
    }

    public function test_read_an_entity_if_none_match_failed()
    {
        $this->assertMetadataResponse(
            Request::factory()
                ->header('if-none-match', 'xxx')
                ->path('/flights(1)')
        );
    }

    public function test_read_an_entity_with_full_metadata()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->metadata(MetadataType\Full::name)
                ->path('/flights(1)')
        );
    }

    public function test_read_an_entity_with_no_metadata()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->metadata(MetadataType\None::name)
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

    public function test_read_with_multiple_select_non_adjacent()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->query('$select', 'origin,gate')
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

    public function test_generated_property()
    {
        $airport = Lodata::getEntityType('airport');

        $property = new class('cp', Type::int32()) extends GeneratedProperty {
            public function invoke(Entity $entity)
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

    public function test_generated_property_selected()
    {
        $airport = Lodata::getEntityType('airport');

        $property = new class('cp', Type::int32()) extends GeneratedProperty {
            public function invoke(Entity $entity)
            {
                return new Int32(4);
            }
        };

        $airport->addProperty($property);
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports(1)')
                ->query('$select', 'code,cp')
        );
    }

    public function test_generated_property_not_selected()
    {
        $airport = Lodata::getEntityType('airport');

        $property = new class('cp', Type::int32()) extends GeneratedProperty {
            public function invoke(Entity $entity)
            {
                return new Int32(4);
            }
        };

        $airport->addProperty($property);
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports(1)')
                ->query('$select', 'code')
        );
    }

    public function test_generated_property_emit()
    {
        $airport = Lodata::getEntityType('airport');

        $property = new class('cp', Type::int32()) extends GeneratedProperty {
            public function invoke(Entity $entity)
            {
                return new Int32(4);
            }
        };

        $airport->addProperty($property);
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports(1)/cp')
        );
    }

    public function test_bad_generated_property()
    {
        $airport = Lodata::getEntityType('airport');

        $property = new class('cp', Type::int32()) extends GeneratedProperty {
            public function invoke(Entity $entity)
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

    public function test_dynamic_property()
    {
        $this->withDynamicPropertyModel();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/example')
        );
    }

    public function test_dynamic_property_select()
    {
        $this->withDynamicPropertyModel();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/example')
                ->select('dynamic')
        );
    }

    public function test_resolve_entity_id()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/$entity')
                ->query('$id', 'flights(1)')
        );
    }

    public function test_resolve_absolute_entity_id()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/$entity')
                ->query('$id', 'http://localhost/odata/flights(1)')
        );
    }

    public function test_resolve_entity_id_with_select()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/$entity')
                ->query('$id', 'flights(1)')
                ->query('$select', 'destination')
        );
    }

    public function test_resolve_entity_id_with_expand()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/$entity')
                ->query('$id', 'flights(1)')
                ->query('$expand', 'passengers')
        );
    }

    public function test_resolve_entity_id_with_type()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/$entity/com.example.odata.flight')
                ->query('$id', 'flights(1)')
        );
    }

    public function test_resolve_entity_id_with_incorrect_type()
    {
        $this->assertNotFound(
            Request::factory()
                ->path('/$entity/com.example.odata.passenger')
                ->query('$id', 'flights(1)')
        );
    }
}
