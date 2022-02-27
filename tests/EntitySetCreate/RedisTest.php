<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\EntitySetCreate;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Drivers\WithRedisDriver;
use Flat3\Lodata\Tests\Helpers\Request;

class RedisTest extends EntitySetCreateTest
{
    use WithRedisDriver;

    public function test_create()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->post()
                ->path($this->entitySetPath)
                ->body([
                    'key' => 'zeta',
                    'name' => 'Zeta',
                ]),
            Response::HTTP_CREATED
        );

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
        );

        $this->assertRedisRecord('zeta');
    }

    public function test_create_return_minimal()
    {
        $response = $this->assertNoContent(
            (new Request)
                ->path($this->entitySetPath)
                ->preference('return', 'minimal')
                ->post()
                ->body([
                    'key' => 'zeta',
                    'name' => 'Zeta',
                ])
        );

        $this->assertResponseHeaderSnapshot($response);
    }

    public function test_create_ref()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->path($this->entitySetPath.'/$ref')
                ->post()
                ->body([
                    'key' => 'zeta',
                    'name' => 'Zeta',
                ]),
            Response::HTTP_CREATED
        );
    }

    public function test_create_without_key()
    {
        $this->assertBadRequest(
            (new Request)
                ->body([
                    'name' => 'hello',
                ])
                ->post()
                ->path($this->entitySetPath)
        );
    }

    public function test_modified_source_name()
    {
        $passengerSet = Lodata::getEntitySet($this->entitySet);
        $ageProperty = $passengerSet->getType()->getProperty('age');
        $ageProperty->setName('aage');
        $passengerSet->getType()->getProperties()->reKey();
        $passengerSet->setPropertySourceName($ageProperty, 'age');

        $this->assertJsonMetadataResponse(
            (new Request)
                ->post()
                ->path($this->entitySetPath)
                ->body([
                    'key' => 'zeta',
                    'name' => 'Zeta',
                    'aage' => 22,
                ]),
            Response::HTTP_CREATED
        );

        $this->assertRedisRecord('zeta');
    }

    public function test_enum_property()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->path($this->entitySetPath)
                ->body([
                    'key' => 'zeta',
                    'name' => 'Zeta',
                    'colour' => 'Blue',
                    'sock_colours' => 'Green,Red',
                ]),
            Response::HTTP_CREATED
        );
    }

    public function test_collection_property()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->path($this->entitySetPath)
                ->body([
                    'key' => 'zeta',
                    'name' => 'Zeta',
                    'emails' => [
                        'oob@example.com',
                        'oo@test.com',
                    ],
                ]),
            Response::HTTP_CREATED
        );
    }
}