<?php

namespace Flat3\Lodata\Tests\Unit\Filesystem;

use Carbon\Carbon;
use Exception;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Drivers\FilesystemEntitySet;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MetadataType;
use Flat3\Lodata\Type\DateTimeOffset;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\Vfs\VfsAdapter;
use RuntimeException;
use VirtualFileSystem\Exception\NotFileException;
use VirtualFileSystem\Exception\NotFoundException;
use VirtualFileSystem\FileSystem as Vfs;

class FilesystemTest extends TestCase
{
    protected $vfs;

    public function setUp(): void
    {
        parent::setUp();

        $this->vfs = $vfs = new Vfs();

        $adapter = new class($vfs) extends VfsAdapter {
            public $vfsInstance;

            public function __construct(Vfs $vfs)
            {
                parent::__construct($vfs);
                $this->vfsInstance = $vfs;
            }

            public function write($path, $contents, Config $config)
            {
                $result = parent::write($path, $contents, $config);
                foreach ($this->listContents('', true) as $item) {
                    $this->vfsInstance->container()->nodeAt($item['path'])->setModificationTime(Carbon::createFromTimeString('2020-01-01 01:01:01')->getTimestamp());
                }
                return $result;
            }

            public function listContents($directory = '', $recursive = false)
            {
                return array_filter(parent::listContents($directory, $recursive), function ($node) {
                    return $node['path'] !== $this->vfsInstance->scheme().':';
                });
            }
        };
        $filesystem = new Filesystem($adapter, ['url' => 'http://odata.files']);
        Storage::extend('vfs', function () use ($filesystem) {
            return $filesystem;
        });
        config(['filesystems.disks.testing' => ['driver' => 'vfs']]);
        $disk = Storage::disk('testing');
        $disk->put('a1.txt', 'hello');
        $disk->put('d1/a1.txt', 'hello1');

        Lodata::add(new FilesystemEntitySet('testfiles', $disk));
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
                ->path('/testfiles')
        );
    }

    public function test_count()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->text()
                ->path('/testfiles/$count')
        );
    }

    public function test_read()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path("/testfiles('a1.txt')")
        );
    }

    public function test_read_with_metadata()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->metadata(MetadataType\Full::name)
                ->path("/testfiles('a1.txt')")
        );
    }

    public function test_read_file_in_directory()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path("/testfiles('d1%2Fa1.txt')")
        );
    }

    public function test_delete()
    {
        $this->assertNoContent(
            Request::factory()
                ->delete()
                ->path("/testfiles('a1.txt')")
        );

        try {
            $this->vfs->container()->fileAt('a1.txt');
            throw new RuntimeException('Failed to throw exception');
        } catch (NotFileException | NotFoundException $e) {
        } catch (Exception $e) {
            throw new RuntimeException('Failed to throw exception');
        }

        $this->vfs->container()->fileAt('d1/a1.txt');
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
                ->path("/testfiles"),
            Response::HTTP_CREATED
        );
    }
}