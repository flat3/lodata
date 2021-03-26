<?php

namespace Flat3\Lodata\Tests\Unit\Modify;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\Metadata;

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

    public function test_create_related_entity()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)/passengers')
                ->post()
                ->body([
                    'name' => 'Henry Horse',
                ]),
            Response::HTTP_CREATED
        );

        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)/passengers')
        );
    }

    public function test_create_ref()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights/$ref')
                ->post()
                ->body([
                    'origin' => 'lhr',
                ]),
            Response::HTTP_CREATED
        );
    }

    public function test_create_deep()
    {
        $this->assertJsonResponse(
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
                ]),
            Response::HTTP_CREATED
        );
    }

    public function test_create_deep_metadata()
    {
        $response = $this->jsonResponse($this->assertJsonResponse(
            Request::factory()
                ->path('/flights')
                ->metadata(Metadata\Full::name)
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
                ]),
            Response::HTTP_CREATED
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