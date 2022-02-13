<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Helpers\Flysystem;

use Carbon\Carbon;
use Flat3\Lodata\Tests\Helpers\TestFilesystemAdapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

/**
 * Flysystem 3.x test adapter
 */
class TestFilesystemAdapter3 extends LocalFilesystemAdapter
{
    public function bind()
    {
        Storage::extend('vfs', function ($app, $config) {
            $config['url'] = 'http://odata.files';
            $config['root'] = TestFilesystemAdapter::root();
            $adapter = new TestFilesystemAdapter3($config['root']);

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });
    }

    public function listContents(string $path, bool $deep): iterable
    {
        foreach (parent::listContents($path, $deep) as $storageAttributes) {
            if ($storageAttributes instanceof FileAttributes) {
                yield new FileAttributes(
                    $storageAttributes->path(),
                    $storageAttributes->fileSize(),
                    $storageAttributes->visibility(),
                    (new Carbon(TestFilesystemAdapter::$timestamp))->getTimestamp(),
                    $storageAttributes->mimeType(),
                    $storageAttributes->extraMetadata(),
                );
            } else {
                yield new DirectoryAttributes(
                    $storageAttributes->path(),
                    $storageAttributes->visibility(),
                    (new Carbon(TestFilesystemAdapter::$timestamp))->getTimestamp(),
                    $storageAttributes->extraMetadata()
                );
            }
        }
    }

    public function lastModified(string $path): FileAttributes
    {
        $fileAttributes = parent::lastModified($path);

        return new FileAttributes(
            $fileAttributes->path(),
            $fileAttributes->fileSize(),
            $fileAttributes->visibility(),
            (new Carbon(TestFilesystemAdapter::$timestamp))->getTimestamp(),
            $fileAttributes->mimeType(),
            $fileAttributes->extraMetadata()
        );
    }
}