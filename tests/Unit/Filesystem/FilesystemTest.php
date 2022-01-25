<?php

namespace Flat3\Lodata\Tests\Unit\Filesystem;

use Carbon\CarbonImmutable as Carbon;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Drivers\FilesystemEntitySet;
use Flat3\Lodata\Drivers\FilesystemEntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MetadataType;
use Flat3\Lodata\Type\DateTimeOffset;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class FilesystemTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('testing');
        $disk->put('a1.txt', 'hello');
        $disk->put('d1/a1.txt', 'hello1');

        $entitySet = new FilesystemEntitySet('files', new FilesystemEntityType());
        $entitySet->setDisk($disk);

        Lodata::add($entitySet);
    }

    public function test_metadata()
    {
        $this->assertMetadataDocuments();
    }

    public function test_set()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/files')
        );
    }

    public function test_set_with_metadata()
    {
        $this->assertJsonResponse(
            (new Request)
                ->metadata(MetadataType\Full::name)
                ->path('/files')
        );
    }

    public function test_count()
    {
        $this->assertNotImplemented(
            (new Request)
                ->text()
                ->path('/files/$count')
        );
    }

    public function test_read()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path("/files('a1.txt')")
        );
    }

    public function test_read_not_found()
    {
        $this->assertNotFound(
            (new Request)
                ->path("/files('qq.txt')")
        );
    }

    public function test_read_key_as_segment()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/files/a1.txt')
        );
    }

    public function test_read_with_metadata()
    {
        $this->assertJsonResponse(
            (new Request)
                ->metadata(MetadataType\Full::name)
                ->path("/files('a1.txt')")
        );
    }

    public function test_read_file_in_directory()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path("/files('d1%2Fa1.txt')")
        );
    }

    public function test_read_file_in_directory_key_as_segment()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/files/d1%2Fa1.txt')
        );
    }

    public function test_delete()
    {
        $this->assertNoContent(
            (new Request)
                ->delete()
                ->path("/files('a1.txt')")
        );

        $this->assertFalse($this->getDisk()->exists('a1.txt'));
    }

    public function test_create()
    {
        $this->assertJsonResponse(
            (new Request)
                ->body([
                    'path' => 'd1/a2.txt',
                    'timestamp' => (new DateTimeOffset(Carbon::createFromTimeString('2020-01-01 01:01:01')))->toJson(),
                ])
                ->post()
                ->path('/files'),
            Response::HTTP_CREATED
        );

        $this->assertMatchesTextSnapshot($this->getDisk()->get('d1/a2.txt'));
    }

    public function test_create_with_content()
    {
        $this->assertJsonResponse(
            (new Request)
                ->body([
                    'path' => 'd1/a3.txt',
                    'timestamp' => (new DateTimeOffset(Carbon::createFromTimeString('2020-01-01 01:01:01')))->toJson(),
                    '$value' => 'dGVzdA==',
                ])
                ->post()
                ->path('/files'),
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
                ->path('/files')
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
                ->path('/files')
        );
    }

    public function test_update_with_content()
    {
        $this->getDisk()->put('c1.txt', '');
        $this->assertJsonResponse(
            (new Request)
                ->body([
                    '$value' => 'dGVzdA==',
                ])
                ->put()
                ->path("/files('c1.txt')")
        );

        $this->assertMatchesTextSnapshot($this->getDisk()->get('c1.txt'));
    }

    public function test_media_stream()
    {
        $this->assertFound(
            (new Request)
                ->path("/files('a1.txt')/content/\$value")
        );
    }

    public function test_read_with_embedded_stream()
    {
        $this->assertJsonResponse(
            (new Request)
                ->select('content')
                ->path("/files('a1.txt')")
        );
    }
}