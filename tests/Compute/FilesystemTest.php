<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Compute;

use Flat3\Lodata\Tests\Drivers\WithFilesystemDriver;

class FilesystemTest extends ComputeTest
{
    use WithFilesystemDriver;

    protected $computeString = 'path';
    protected $computeDate = 'timestamp';
    protected $computeFloat = 'size';
}