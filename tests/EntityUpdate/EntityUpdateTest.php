<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\EntityUpdate;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

abstract class EntityUpdateTest extends TestCase
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

    public function test_update_invalid_property()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entityPath)
                ->patch()
                ->body([
                    'origin' => 'ooo',
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
}