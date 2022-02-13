<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper\Flysystem;

use Flat3\Lodata\Helper\Filesystem;
use League\Flysystem\Adapter\Local;
use League\Flysystem\FileNotFoundException;

/**
 * Flysystem 1.x compatibility
 * @package Flat3\Lodata\Helper\Flysystem
 */
class Flysystem1 extends Filesystem
{
    public function listContents($directory = '', $recursive = false): iterable
    {
        return array_filter($this->disk->listContents($directory, $recursive), function ($metadata) {
            return $metadata['type'] === 'file';
        });
    }

    public function getMetadata($path): ?array
    {
        try {
            $metadata = $this->disk->getMetadata($path);

            return $metadata['type'] === 'file' ? $metadata : null;
        } catch (FileNotFoundException $exception) {
            return null;
        }
    }

    public function isLocal(): bool
    {
        return $this->disk->getAdapter() instanceof Local;
    }
}