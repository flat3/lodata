<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Compute;

use Flat3\Lodata\Tests\Drivers\WithFilesystemDriver;

/**
 * @group filesystem
 */
class FilesystemTest extends Compute
{
    use WithFilesystemDriver;

    protected $computeString = 'path';
    protected $computeDate = 'timestamp';
    protected $computeFloat = 'size';
}
