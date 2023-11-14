<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\EntitySetCreate;

use Carbon\CarbonImmutable as Carbon;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Tests\Drivers\WithFilesystemDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Type\DateTimeOffset;

/**
 * @group filesystem
 */
class FilesystemTest extends EntitySetCreate
{
    use WithFilesystemDriver;

    public function test_create()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->body([
                    'path' => 'd1/a2.txt',
                    'timestamp' => (new DateTimeOffset(Carbon::createFromTimeString('2020-01-01 01:01:01')))->toJson(),
                    '$value' => 'dGVzdA==',
                ])
                ->post()
                ->path($this->entitySetPath),
            Response::HTTP_CREATED
        );

        $this->assertMatchesTextSnapshot($this->getDisk()->get('d1/a2.txt'));
    }

    public function test_create_ref()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->path($this->entitySetPath.'/$ref')
                ->post()
                ->body([
                    'path' => 'd1/a2.txt',
                    'timestamp' => (new DateTimeOffset(Carbon::createFromTimeString('2020-01-01 01:01:01')))->toJson(),
                    '$value' => 'dGVzdA==',
                ]),
            Response::HTTP_CREATED
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
                    'path' => 'd1/a2.txt',
                    'timestamp' => (new DateTimeOffset(Carbon::createFromTimeString('2020-01-01 01:01:01')))->toJson(),
                    '$value' => 'dGVzdA==',
                ])
        );

        $this->assertResponseHeaderSnapshot($response);
    }

    public function test_create_with_content()
    {
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->body([
                    'path' => 'd1/a3.txt',
                    'timestamp' => (new DateTimeOffset(Carbon::createFromTimeString('2020-01-01 01:01:01')))->toJson(),
                    '$value' => 'dGVzdA==',
                ])
                ->post()
                ->path($this->entitySetPath),
            Response::HTTP_CREATED
        );

        $this->assertMatchesTextSnapshot($this->getDisk()->get('d1/a3.txt'));
    }

    public function test_create_missing_path()
    {
        $this->assertBadRequest(
            (new Request)
                ->body([])
                ->post()
                ->path($this->entitySetPath)
        );
    }

    public function test_create_conflicts()
    {
        $this->getDisk()->put('c1.txt', '');

        $this->assertConflict(
            (new Request)
                ->body([
                    'path' => 'c1.txt',
                ])
                ->post()
                ->path($this->entitySetPath)
        );
    }

    public function test_modified_source_name()
    {
        $this->expectNotToPerformAssertions();
    }

    public function test_rejects_long_values()
    {
        $this->expectNotToPerformAssertions();
    }

    public function test_enum_property()
    {
        $this->expectNotToPerformAssertions();
    }

    public function test_collection_property()
    {
        $this->expectNotToPerformAssertions();
    }

    public function test_creates_with_immutable()
    {
        $this->expectNotToPerformAssertions();
    }
}
