<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Helpers;

use Flat3\Lodata\Tests\Helpers\Flysystem\TestFilesystemAdapter1;
use Flat3\Lodata\Tests\Helpers\Flysystem\TestFilesystemAdapter3;

class TestFilesystemAdapter
{
    public static $timestamp = '2020-01-01T01:01:01+00:00';

    public static function root(): string
    {
        return storage_path('framework/testing/disks/'.gethostname().getmypid().getenv('TEST_TOKEN'));
    }

    public static function bind()
    {
        $adapter = class_exists('League\Flysystem\Local\LocalFilesystemAdapter') ?
            new TestFilesystemAdapter3(self::root()) : new TestFilesystemAdapter1;

        $adapter->bind();
    }
}