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