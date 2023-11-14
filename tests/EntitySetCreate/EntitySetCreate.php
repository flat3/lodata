<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\EntitySetCreate;

use Flat3\Lodata\Annotation\Core\V1\Immutable;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

abstract class EntitySetCreate extends TestCase
{
    public function test_create()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->post()
                ->path($this->entitySetPath)
                ->body([
                    'name' => 'Oobleck',
                ]),
            Response::HTTP_CREATED
        );

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
        );
    }

    public function test_create_ref()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->path($this->entitySetPath.'/$ref')
                ->post()
                ->body([
                    'name' => 'lhr',
                ]),
            Response::HTTP_CREATED
        );
    }

    public function test_create_rejects_invalid_property()
    {
        if (Lodata::getEntitySet($this->entitySet)->getType()->isOpen()) {
            $this->markTestSkipped();
        }

        $this->assertNotAcceptable(
            (new Request)
                ->path($this->entitySetPath)
                ->post()
                ->body([
                    'name' => 'lhr',
                    'invalid' => 'ooo',
                ])
        );
    }

    public function test_create_accepts_invalid_property()
    {
        if (!Lodata::getEntitySet($this->entitySet)->getType()->isOpen()) {
            $this->markTestSkipped();
        }

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
                ->post()
                ->body([
                    'name' => 'lhr',
                    'invalid' => 'ooo',
                ]),
            Response::HTTP_CREATED
        );
    }

    public function test_create_rejects_null_properties()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entitySetPath)
                ->post()
                ->body([
                    'name' => null,
                    'age' => 4,
                ])
        );
    }

    public function test_create_content_type_error()
    {
        $this->assertNotAcceptable(
            (new Request)
                ->path($this->entitySetPath)
                ->text()
                ->post()
                ->body([
                    'name' => 'lhr',
                ])
        );
    }

    public function test_create_return_minimal()
    {
        $response = $this->assertNoContent(
            (new Request)
                ->path($this->entitySetPath)
                ->preference('return', 'minimal')
                ->post()
                ->body([
                    'name' => 'lhr',
                    'age' => 4,
                ])
        );

        $this->assertResponseHeaderSnapshot($response);
    }

    public function test_rejects_null_properties()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entitySetPath)
                ->post()
                ->body([
                    'name' => null,
                ])
        );
    }

    public function test_rejects_long_values()
    {
        $this->assertBadRequest(
            (new Request)
                ->path($this->entitySetPath)
                ->post()
                ->body([
                    'name' => str_repeat('a', 256),
                ])
        );
    }

    public function test_modified_source_name()
    {
        $this->withModifiedPropertySourceName();

        $this->assertJsonMetadataResponse(
            (new Request)
                ->post()
                ->path($this->entitySetPath)
                ->body([
                    'name' => 'Oobleck',
                    'aage' => 22,
                ]),
            Response::HTTP_CREATED
        );
    }

    public function test_enum_property()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->path($this->entitySetPath)
                ->body([
                    'name' => 'Oobleck',
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
                    'name' => 'Oobleck',
                    'emails' => [
                        'oob@example.com',
                        'oo@test.com',
                    ],
                ]),
            Response::HTTP_CREATED
        );
    }

    public function test_creates_with_immutable()
    {
        $type = Lodata::getEntitySet($this->entitySet)->getType();
        $type->getProperty('age')->addAnnotation(new Immutable);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->path($this->entitySetPath)
                ->body([
                    'name' => 'Four',
                    'age' => 4,
                ]),
            Response::HTTP_CREATED
        );
    }
}
