<?php

namespace Flat3\Lodata\Tests\Unit\Filesystem;

use Carbon\Carbon;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Drivers\FilesystemEntitySet;
use Flat3\Lodata\Drivers\FilesystemEntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MetadataType;
use Flat3\Lodata\Type\DateTimeOffset;
use Illuminate\Support\Facades\Storage;

class FilesystemTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $disk = Storage::disk('testing');
        $disk->put('a1.txt', 'hello');
        $disk->put('d1/a1.txt', 'hello1');

        $entitySet = new FilesystemEntitySet('files', new FilesystemEntityType());
        $entitySet->setDisk($disk);

        Lodata::add($entitySet);
    }

    public function test_metadata()
    {
        $this->assertXmlResponse(
            Request::factory()
                ->path('/$metadata')
                ->xml()
        );
    }

    public function test_set()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/files')
        );
    }

    public function test_set_with_metadata()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->metadata(MetadataType\Full::name)
                ->path('/files')
        );
    }

    public function test_count()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->text()
                ->path('/files/$count')
        );
    }

    public function test_read()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path("/files('a1.txt')")
        );
    }

    public function test_read_with_metadata()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->metadata(MetadataType\Full::name)
                ->path("/files('a1.txt')")
        );
    }

    public function test_read_file_in_directory()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path("/files('d1%2Fa1.txt')")
        );
    }

    public function test_delete()
    {
        $this->assertNoContent(
            Request::factory()
                ->delete()
                ->path("/files('a1.txt')")
        );

        $this->assertFalse($this->getDisk()->exists('a1.txt'));
    }

    public function test_create()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->body([
                    'path' => 'd1/a2.txt',
                    'timestamp' => DateTimeOffset::factory(Carbon::createFromTimeString('2020-01-01 01:01:01'))->toJson(),
                ])
                ->post()
                ->path("/files"),
            Response::HTTP_CREATED
        );
    }
}