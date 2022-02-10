<?php

declare(strict_types=1);

namespace Flat3\Lodata\Traits;

use Flat3\Lodata\Helper\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

trait HasDisk
{
    /** @var FilesystemAdapter $disk */
    protected $disk;

    /**
     * Set the disk by name
     * @param  string  $name  Disk name
     * @return $this
     */
    public function setDiskName(string $name): self
    {
        $this->disk = Storage::disk($name);

        return $this;
    }

    /**
     * Set the disk by filesystem adaptor
     * @param  FilesystemAdapter  $disk  Filesystem
     * @return $this
     */
    public function setDisk(FilesystemAdapter $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * Get a Filesystem instance to work with the disk
     * @return Filesystem
     */
    public function getFilesystem(): Filesystem
    {
        return App::makeWith(Filesystem::class)->setDisk($this->disk);
    }
}