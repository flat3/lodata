<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Entity;

use Flat3\Lodata\ComplexValue;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\GeneratedProperty;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MetadataType;
use Flat3\Lodata\Type;
use Flat3\Lodata\Type\Int32;
use Flat3\Lodata\Type\String_;

abstract class Entity extends TestCase
{
    public function test_read()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath.'('.$this->escapedEntityId.')')
        );
    }

    public function test_read_key_as_segment()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
        );
    }

    public function test_read_key_as_long_form()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath.'('.$this->entitySetKey.'='.$this->escapedEntityId.')')
        );
    }

    public function test_read_alternative_key()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath."(code='elo')")
        );
    }

    public function test_read_select()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->select('dob')
        );
    }

    public function test_read_multiple_select()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->select('name,dob')
        );
    }

    public function test_read_property()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath.'/dob')
        );
    }

    public function test_read_collection_property()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath.'/emails')
        );
    }

    public function test_missing()
    {
        $key = $this->missingEntityId;

        if (is_string($key)) {
            $key = "'{$key}'";
        }

        $this->assertNotFound(
            (new Request)
                ->path(sprintf("%s(%s)", $this->entitySetPath, $key))
        );
    }

    public function test_missing_key_as_segment()
    {
        $this->assertNotFound(
            (new Request)
                ->path($this->entitySetPath.'/'.$this->missingEntityId)
        );
    }

    public function test_read_an_entity_etag()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
        );
    }

    public function test_read_an_entity_etag_select()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->select('name')
        );
    }

    public function test_read_an_entity_if_match()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->header('if-match', $this->etag)
                ->path($this->entityPath)
        );
    }

    public function test_read_an_entity_if_match_failed()
    {
        $this->assertPreconditionFailed(
            (new Request)
                ->header('if-match', 'xxx')
                ->path($this->entityPath)
        );
    }

    public function test_read_an_entity_if_match_any()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->header('if-match', '*')
                ->path($this->entityPath)
        );
    }

    public function test_read_an_entity_if_none_match_any()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->header('if-none-match', '*')
                ->path($this->entityPath)
        );
    }

    public function test_read_an_entity_if_none_match()
    {
        $this->assertNotModified(
            (new Request)
                ->header('if-none-match', $this->etag)
                ->path($this->entityPath)
        );
    }

    public function test_read_an_entity_if_none_match_failed()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->header('if-none-match', 'xxx')
                ->path($this->entityPath)
        );
    }

    public function test_read_an_entity_with_full_metadata()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->metadata(MetadataType\Full::name)
                ->path($this->entityPath)
        );
    }

    public function test_read_an_entity_with_no_metadata()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->metadata(MetadataType\None::name)
                ->path($this->entityPath)
        );
    }

    public function test_read_a_qualified_entity()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/com.example.odata.'.$this->entitySet.'/'.$this->entityId)
        );
    }

    public function test_read_an_entity_with_referenced_key()
    {
        $key = Lodata::getEntitySet($this->entitySet)->getType()->getKey()->getName();
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath.'('.$key.'=@id)')
                ->query('@id', (string) $this->escapedEntityId)
        );
    }

    public function test_read_an_entity_with_invalid_key()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entitySetPath."(origin='lax')")
        );
    }

    public function test_read_an_entity_with_invalid_referenced_key()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entitySetPath.'(origin=@origin)')
                ->query('@origin', 'lax')
        );
    }

    public function test_empty_select_ignored()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->select('')
        );
    }

    public function test_select_star()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->select('*')
        );
    }

    public function test_read_an_entity_set_primitive()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath.'/name')
        );
    }

    public function test_cannot_delete_a_non_null_entity_set_primitive()
    {
        $this->assertBadRequest(
            (new Request)
                ->delete()
                ->path($this->entityPath.'/'.Lodata::getEntitySet($this->entitySet)->getType()->getKey()->getName())
        );
    }

    public function test_null_no_content()
    {
        $altKey = is_string($this->entityId) ? 'delta' : $this->entityId + 3;

        $this->assertNoContent(
            (new Request)
                ->path(sprintf("%s/%s/age", $this->entitySetPath, $altKey))
        );
    }

    public function test_raw()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->text()
                ->path($this->entityPath.'/name/$value')
        );
    }

    public function test_raw_collection_error()
    {
        $this->assertBadRequest(
            (new Request)
                ->text()
                ->path($this->entityPath.'/emails/$value')
        );
    }

    public function test_null_raw_not_found()
    {
        $this->assertNotFound(
            (new Request)
                ->text()
                ->path(sprintf("%s/%s/origin/\$value", $this->entitySetPath, $this->missingEntityId))
        );
    }

    public function test_null_raw_no_content()
    {
        $altKey = is_string($this->entityId) ? 'delta' : $this->entityId + 3;

        $this->assertNoContent(
            (new Request)
                ->text()
                ->path(sprintf("%s/%s/dob/\$value", $this->entitySetPath, $altKey))
        );
    }

    public function test_not_entity_or_set_not_found()
    {
        $this->assertNotFound(
            (new Request)
                ->path($this->entityPath.'/origin/$ref')
        );
    }

    public function test_not_last_segment()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entityPath.'/$ref/1')
        );
    }

    public function test_entity_references()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath.'/$ref')
        );
    }

    public function test_generated_property()
    {
        $type = Lodata::getEntitySet($this->entitySet)->getType();

        $property = new class('cp', Type::int32()) extends GeneratedProperty {
            public function invoke(ComplexValue $value)
            {
                return new Int32(4);
            }
        };

        $type->addProperty($property);
        $this->assertMetadataSnapshot();
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
        );
    }

    public function test_generated_property_selected()
    {
        $type = Lodata::getEntitySet($this->entitySet)->getType();

        $property = new class('cp', Type::int32()) extends GeneratedProperty {
            public function invoke(ComplexValue $value)
            {
                return new Int32(4);
            }
        };

        $type->addProperty($property);
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->select('dob,cp')
        );
    }

    public function test_generated_property_not_selected()
    {
        $type = Lodata::getEntitySet($this->entitySet)->getType();

        $property = new class('cp', Type::int32()) extends GeneratedProperty {
            public function invoke(ComplexValue $value)
            {
                return new Int32(4);
            }
        };

        $type->addProperty($property);
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->select('dob')
        );
    }

    public function test_generated_property_emit()
    {
        $type = Lodata::getEntitySet($this->entitySet)->getType();

        $property = new class('cp', Type::int32()) extends GeneratedProperty {
            public function invoke(ComplexValue $value)
            {
                return new Int32(4);
            }
        };

        $type->addProperty($property);
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath.'/cp')
        );
    }

    public function test_bad_generated_property()
    {
        $type = Lodata::getEntitySet($this->entitySet)->getType();

        $property = new class('cp', Type::int32()) extends GeneratedProperty {
            public function invoke(ComplexValue $value)
            {
                return new String_(4);
            }
        };

        $type->addProperty($property);

        ob_start();

        $this->assertTextMetadataResponse(
            (new Request)
                ->path($this->entityPath)
        );

        $this->assertMatchesSnapshot(ob_get_clean());
    }

    public function test_resolve_entity_id()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/$entity')
                ->id(sprintf("%s(%s)", $this->entitySet, $this->escapedEntityId))
        );
    }

    public function test_resolve_absolute_entity_id()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/$entity')
                ->id(sprintf("http://localhost/odata/%s(%s)", $this->entitySet, $this->escapedEntityId))
        );
    }

    public function test_resolve_entity_id_with_select()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/$entity')
                ->id(sprintf("%s(%s)", $this->entitySet, $this->escapedEntityId))
                ->select('name')
        );
    }

    public function test_resolve_entity_id_with_type()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/$entity/'.Lodata::getEntitySet($this->entitySet)->getType()->getIdentifier())
                ->id(sprintf("%s(%s)", $this->entitySet, $this->escapedEntityId))
        );
    }

    public function test_resolve_entity_id_with_incorrect_type()
    {
        $this->assertNotFound(
            (new Request)
                ->path('/$entity/com.example.odata.airport')
                ->id(sprintf("%s(%s)", $this->entitySet, $this->escapedEntityId))
        );
    }

    public function test_modified_source_name()
    {
        $this->withModifiedPropertySourceName();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath.'('.$this->escapedEntityId.')')
        );
    }
}
