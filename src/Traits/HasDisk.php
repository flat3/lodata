<?php

namespace Flat3\Lodata\Traits;

use Illuminate\Filesystem\FilesystemAdapter;
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
     * Get the attached disk
     * @return FilesystemAdapter Disk
     */
    public function getDisk(): FilesystemAdapter
    {
        return $this->disk;
    }
}