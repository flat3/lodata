<?php

namespace Flat3\Lodata\Tests\Unit\Modify;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MetadataType;

class CreateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function tearDown(): void
    {
        $this->assertDatabaseSnapshot();
        parent::tearDown();
    }

    public function test_create()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/flights')
                ->post()
                ->body([
                    'origin' => 'lhr',
                ])
        );
    }

    public function test_create_content_type_error()
    {
        $this->assertNotAcceptable(
            Request::factory()
                ->path('/flights')
                ->text()
                ->post()
                ->body([
                    'origin' => 'lhr',
                ])
        );
    }

    public function test_create_return_minimal()
    {
        $response = $this->assertNoContent(
            Request::factory()
                ->path('/flights')
                ->preference('return', 'minimal')
                ->post()
                ->body([
                    'origin' => 'lhr',
                    'destination' => 'lax',
                ])
        );

        $this->assertResponseMetadata($response);
    }

    public function test_create_related_entity()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/flights(1)/passengers')
                ->post()
                ->body([
                    'name' => 'Henry Horse',
                ])
        );

        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)/passengers')
        );
    }

    public function test_create_entity_with_existing_related_entities()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/flights')
                ->post()
                ->body([
                    'origin' => 'sfo',
                    'destination' => 'lhr',
                    'passengers' => [
                        [
                            '@id' => 'passengers(1)',
                        ],
                        [
                            '@id' => 'passengers(2)',
                        ],
                    ]
                ])
        );
    }

    public function test_create_entity_cannot_modify_existing_related_entities()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/flights')
                ->post()
                ->body([
                    'origin' => 'sfo',
                    'destination' => 'lhr',
                    'passengers' => [
                        [
                            '@id' => 'passengers(1)',
                            'name' => 'Not allowed',
                        ],
                    ]
                ])
        );
    }

    public function test_create_ref()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/flights/$ref')
                ->post()
                ->body([
                    'origin' => 'lhr',
                ])
        );
    }

    public function test_create_deep()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/flights')
                ->post()
                ->body([
                    'origin' => 'lhr',
                    'destination' => 'sfo',
                    'passengers' => [
                        [
                            'name' => 'Alice',
                            'pets' => [
                                [
                                    'name' => 'Sparkles',
                                    'type' => 'dog',
                                ]
                            ],
                        ],
                        [
                            'name' => 'Bob',
                        ],
                    ],
                ])
        );
    }

    public function test_create_deep_metadata()
    {
        $response = $this->jsonResponse($this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/flights')
                ->metadata(MetadataType\Full::name)
                ->post()
                ->body([
                    'origin' => 'lhr',
                    'destination' => 'sfo',
                    'passengers' => [
                        [
                            'name' => 'Alice',
                            'pets' => [
                                [
                                    'name' => 'Sparkles',
                                    'type' => 'dog',
                                ]
                            ],
                        ],
                        [
                            'name' => 'Bob',
                        ],
                    ],
                ])
        ));

        $this->assertJsonResponse(
            $this->urlToReq($response->{'passengers@navigationLink'})
        );

        $this->assertJsonResponse(
            $this->urlToReq($response->passengers[0]->{'@readLink'})
        );

        $this->assertJsonResponse(
            $this->urlToReq($response->passengers[0]->{'pets@navigationLink'})
        );
    }
}