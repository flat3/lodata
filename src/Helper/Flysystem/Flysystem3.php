<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper\Flysystem;

use Flat3\Lodata\Helper\Filesystem;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\Local\LocalFilesystemAdapter;

/**
 * Flysystem 3.x compatibility
 * @package Flat3\Lodata\Helper\Flysystem
 */
class Flysystem3 extends Filesystem
{
    public function listContents($directory = '', $recursive = false): iterable
    {
        foreach ($this->disk->listContents($directory, $recursive)->sortByPath() as $fileAttributes) {
            if ($fileAttributes instanceof DirectoryAttributes) {
                continue;
            }

            yield [
                'path' => $fileAttributes->path(),
                'timestamp' => $fileAttributes->lastModified(),
                'type' => $fileAttributes->type(),
                'size' => $fileAttributes->fileSize(),
            ];
        }
    }

    public function getMetadata($path): ?array
    {
        if (!$this->disk->fileExists($path)) {
            return null;
        }

        return [
            'type' => $this->disk->mimeType($path),
            'path' => $path,
            'timestamp' => $this->disk->lastModified($path),
            'size' => $this->disk->size($path),
        ];
    }

    public function isLocal(): bool
    {
        return $this->disk->getAdapter() instanceof LocalFilesystemAdapter;
    }
}