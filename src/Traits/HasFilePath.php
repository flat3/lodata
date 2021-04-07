<?php

namespace Flat3\Lodata\Traits;

trait HasFilePath
{
    /** @var string $filePath */
    protected $filePath;

    /**
     * Set the path
     * @param  string  $path  Path
     * @return $this
     */
    public function setFilePath(string $path): self
    {
        $this->filePath = $path;

        return $this;
    }

    /**
     * Get the path
     * @return string Path
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }
}