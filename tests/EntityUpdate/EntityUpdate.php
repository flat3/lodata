<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\EntityUpdate;

use Flat3\Lodata\Annotation\Core\V1\Immutable;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

abstract class EntityUpdate extends TestCase
{
    public function test_delete()
    {
        $this->assertNoContent(
            (new Request)
                ->delete()
                ->path($this->entityPath)
        );

        $this->assertNotFound(
            (new Request)
                ->path($this->entityPath)
        );
    }

    public function test_update()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->patch()
                ->body([
                    'name' => 'Alph',
                ])
        );
    }

    public function test_upsert_key_as_segment()
    {
        $response = (new Request)
            ->path($this->entitySetPath.'/'.$this->missingEntityId)
            ->patch()
            ->body([
                'name' => 'Alph',
            ]);

        if (Lodata::getEntitySet($this->entitySet)->getType()->getKey()->isComputed()) {
            $this->assertBadRequest($response);
        } else {
            $this->assertJsonResponseSnapshot($response, Response::HTTP_CREATED);
        }
    }

    public function test_upsert()
    {
        $response = (new Request)
            ->path($this->entitySetPath.'('.$this->escapedMissingEntityId.')')
            ->patch()
            ->body([
                'name' => 'Alph',
            ]);

        if (Lodata::getEntitySet($this->entitySet)->getType()->getKey()->isComputed()) {
            $this->assertBadRequest($response);
        } else {
            $this->assertJsonResponseSnapshot($response, Response::HTTP_CREATED);
        }
    }

    public function test_update_put()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->put()
                ->body([
                    'name' => 'Alph',
                ])
        );
    }

    public function test_update_post()
    {
        $this->assertMethodNotAllowed(
            (new Request)
                ->path($this->entityPath)
                ->post()
                ->body([
                    'name' => 'Alph',
                ])
        );
    }

    public function test_update_post_via_tunnel()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->post()
                ->header('x-http-method', 'patch')
                ->body([
                    'name' => 'Alph',
                ])
        );
    }

    public function test_validate_etag()
    {
        $response = $this->req(
            (new Request)
                ->path($this->entityPath)
        );

        $etag = $response->headers->get('etag');

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->header('if-match', $etag)
                ->put()
                ->body([
                    'name' => 'Alph',
                ])
        );

        $this->assertPreconditionFailed(
            (new Request)
                ->path($this->entityPath)
                ->header('if-match', $etag)
                ->put()
                ->body([
                    'name' => 'Alh',
                ])
        );

        $this->assertPreconditionFailed(
            (new Request)
                ->path($this->entityPath)
                ->header('if-match', [$etag])
                ->put()
                ->body([
                    'name' => 'lh',
                ])
        );
    }

    public function test_multiple_etag()
    {
        $response = $this->req(
            (new Request)
                ->path($this->entityPath)
        );

        $etag = $response->headers->get('etag');

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->header('if-match', ['xyz', $etag])
                ->put()
                ->body([
                    'name' => 'lh',
                ])
        );
    }

    public function test_any_etag()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->header('if-match', '*')
                ->put()
                ->body([
                    'name' => 'lh',
                ])
        );
    }

    public function test_any_if_none_match_any()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->header('if-none-match', '*')
                ->put()
                ->body([
                    'name' => 'lh',
                ])
        );
    }

    public function test_any_if_none_match()
    {
        $response = $this->req(
            (new Request)
                ->path($this->entityPath)
        );

        $etag = $response->headers->get('etag');

        $this->assertPreconditionFailed(
            (new Request)
                ->path($this->entityPath)
                ->header('if-none-match', $etag)
                ->put()
                ->body([
                    'name' => 'lh',
                ])
        );
    }

    public function test_any_if_none_match_failed()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->header('if-none-match', 'xxx')
                ->put()
                ->body([
                    'name' => 'lh',
                ])
        );
    }

    public function test_update_ref()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath.'/$ref')
                ->patch()
                ->body([
                    'name' => 'lh',
                ])
        );
    }

    public function test_update_return_minimal()
    {
        $response = $this->assertNoContent(
            (new Request)
                ->path($this->entityPath)
                ->preference('return', 'minimal')
                ->patch()
                ->body([
                    'name' => 'lh',
                ])
        );

        $this->assertResponseHeaderSnapshot($response);
    }

    public function test_update_rejects_invalid_property()
    {
        if (Lodata::getEntitySet($this->entitySet)->getType()->isOpen()) {
            $this->markTestSkipped();
        }

        $this->assertNotAcceptable(
            (new Request)
                ->path($this->entityPath)
                ->patch()
                ->body([
                    'invalid' => 'ooo',
                ])
        );
    }

    public function test_update_accepts_invalid_property()
    {
        if (!Lodata::getEntitySet($this->entitySet)->getType()->isOpen()) {
            $this->markTestSkipped();
        }

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->patch()
                ->body([
                    'invalid' => 'ooo',
                ])
        );
    }

    public function test_delete_an_entity_set_primitive()
    {
        $this->assertNoContent(
            (new Request)
                ->delete()
                ->path($this->entityPath.'/age')
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

    public function test_update_an_entity_set_primitive()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath.'/name')
                ->patch()
                ->body('sfo')
        );
    }

    public function test_modified_source_name()
    {
        $this->withModifiedPropertySourceName();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->patch()
                ->body([
                    'aage' => '9',
                ])
        );
    }

    public function test_update_enum()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->patch()
                ->body([
                    'colour' => 'Brown',
                ])
        );
    }

    public function test_update_enum_flags()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->patch()
                ->body([
                    'sock_colours' => 'Brown,Blue',
                ])
        );
    }

    public function test_collection_property_put()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath.'/emails')
                ->put()
                ->body([
                    'only@thisemail.com',
                ])
        );
    }

    public function test_collection_property_patch()
    {
        $this->assertMethodNotAllowed(
            (new Request)
                ->path($this->entityPath.'/emails')
                ->patch()
                ->body([
                    'only@thisemail.com',
                ])
        );
    }

    public function test_collection_property_post()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath.'/emails')
                ->post()
                ->body(
                    'added@thisemail.com',
                )
        );
    }

    public function test_collection_property_delete()
    {
        $this->assertNoContent(
            (new Request)
                ->delete()
                ->path($this->entityPath.'/emails'),
        );
    }

    public function test_ignores_immutable()
    {
        $type = Lodata::getEntitySet($this->entitySet)->getType();
        $type->getProperty('name')->addAnnotation(new Immutable);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->patch()
                ->body([
                    'name' => 'Alph',
                ])
        );
    }
}
