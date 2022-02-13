<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Helpers\Flysystem;

use Carbon\Carbon;
use Flat3\Lodata\Tests\Helpers\TestFilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\Filesystem as Flysystem;
use SplFileInfo;

/**
 * Flysystem 1.x test adapter
 */
class TestFilesystemAdapter1
{
    public function bind()
    {
        Storage::extend('vfs', function ($app, $config) {
            return new Flysystem(new class(TestFilesystemAdapter::root()) extends LocalAdapter {
                public function normalizeFileInfo(SplFileInfo $file)
                {
                    return array_merge(
                        parent::normalizeFileInfo($file),
                        ['timestamp' => (new Carbon(TestFilesystemAdapter::$timestamp))->getTimestamp()],
                    );
                }
            }, array_merge(
                $config,
                ['url' => 'http://odata.files'],
            ));
        });
    }
}