<?php

namespace Flat3\Lodata\Tests;

use Carbon\Carbon;
use League\Flysystem\Config;
use VirtualFileSystem\FileSystem as Vfs;

class VfsAdapter extends \League\Flysystem\Vfs\VfsAdapter
{
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
            $this->vfsInstance
                ->container()
                ->nodeAt($item['path'])
                ->setModificationTime(
                    Carbon::createFromTimeString('2020-01-01 01:01:01')
                        ->getTimestamp()
                );
        }

        return $result;
    }

    public function listContents($directory = '', $recursive = false)
    {
        return array_filter(parent::listContents($directory, $recursive), function ($node) {
            return $node['path'] !== $this->vfsInstance->scheme().':';
        });
    }
}