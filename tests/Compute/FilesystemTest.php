<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Compute;

use Flat3\Lodata\Tests\Drivers\WithFilesystemDriver;
use PHPUnit\Framework\Attributes\Group;

/**
 * @group filesystem
 */
#[Group('filesystem')]
class FilesystemTest extends Compute
{
    use WithFilesystemDriver;

    protected $computeString = 'path';
    protected $computeDate = 'timestamp';
    protected $computeFloat = 'size';
}
