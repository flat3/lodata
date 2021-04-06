<?php

namespace Flat3\Lodata\Tests\Unit\Modify;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class UpdateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
        $this->withFlightDataV2();
    }

    public function tearDown(): void
    {
        $this->assertDatabaseSnapshot();
        parent::tearDown();
    }

    public function test_update()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->patch()
                ->body([
                    'origin' => 'ooo',
                ])
        );

        $this->assertDatabaseSnapshot();
    }

    public function test_update_put()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->put()
                ->body([
                    'origin' => 'ooo',
                ])
        );
    }

    public function test_validate_etag()
    {
        $response = $this->req(
            Request::factory()
                ->path('/flights(1)')
        );

        $etag = $response->headers->get('etag');

        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->header('if-match', $etag)
                ->put()
                ->body([
                    'origin' => 'ooo',
                ])
        );

        $this->assertPreconditionFailed(
            Request::factory()
                ->path('/flights(1)')
                ->header('if-match', $etag)
                ->put()
                ->body([
                    'origin' => 'aaa',
                ])
        );

        $this->assertPreconditionFailed(
            Request::factory()
                ->path('/flights(1)')
                ->header('if-match', [$etag])
                ->put()
                ->body([
                    'origin' => 'aaa',
                ])
        );
    }

    public function test_multiple_etag()
    {
        $response = $this->req(
            Request::factory()
                ->path('/flights(1)')
        );

        $etag = $response->headers->get('etag');

        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->header('if-match', ['xyz', $etag])
                ->put()
                ->body([
                    'origin' => 'ooo',
                ])
        );
    }

    public function test_any_etag()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->header('if-match', '*')
                ->put()
                ->body([
                    'origin' => 'ooo',
                ])
        );
    }

    public function test_any_if_none_match_any()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->header('if-none-match', '*')
                ->put()
                ->body([
                    'origin' => 'ooo',
                ])
        );
    }

    public function test_any_if_none_match()
    {
        $response = $this->req(
            Request::factory()
                ->path('/flights(1)')
        );

        $etag = $response->headers->get('etag');

        $this->assertPreconditionFailed(
            Request::factory()
                ->path('/flights(1)')
                ->header('if-none-match', $etag)
                ->put()
                ->body([
                    'origin' => 'ooo',
                ])
        );
    }

    public function test_any_if_none_match_failed()
    {
        $this->assertMetadataResponse(
            Request::factory()
                ->path('/flights(1)')
                ->header('if-none-match', 'xxx')
                ->put()
                ->body([
                    'origin' => 'ooo',
                ])
        );
    }

    public function test_update_ref()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)/$ref')
                ->patch()
                ->body([
                    'origin' => 'ooo',
                ])
        );
    }

    public function test_update_return_minimal()
    {
        $response = $this->assertNoContent(
            Request::factory()
                ->path('/flights(1)')
                ->preference('return', 'minimal')
                ->patch()
                ->body([
                    'origin' => 'ooo',
                ])
        );

        $this->assertResponseMetadata($response);
    }

    public function test_update_invalid_property()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/passengers(1)')
                ->patch()
                ->body([
                    'origin' => 'ooo',
                ])
        );
    }

    public function test_update_related()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/passengers(1)')
                ->patch()
                ->body([
                    'name' => 'Zooey Zamblo',
                    'pets' => [
                        [
                            '@id' => 'pets(1)',
                        ],
                        [
                            '@id' => 'pets(2)',
                            'name' => 'Charlie',
                        ],
                        [
                            'name' => 'Delta',
                        ],
                        [
                            '@id' => 'pets(2)',
                            '@removed' => [
                                'reason' => 'deleted',
                            ],
                        ]
                    ]
                ])
        );
    }

    public function test_update_related_missing()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/passengers(1)')
                ->patch()
                ->body([
                    'name' => 'Zooey Zamblo',
                    'pets' => [
                        [
                            '@id' => 'pets(99)',
                        ],
                    ]
                ])
        );
    }

    public function test_update_removed_changed()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/passengers(1)')
                ->patch()
                ->body([
                    'pets' => [
                        [
                            '@removed' => ['reason' => 'changed'],
                            '@id' => 'pets(1)',
                        ],
                    ]
                ])
        );
    }
}