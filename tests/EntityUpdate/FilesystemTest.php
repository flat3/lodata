<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\EntityUpdate;

use Flat3\Lodata\Tests\Drivers\WithFilesystemDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

/**
 * @group filesystem
 */
class FilesystemTest extends TestCase
{
    use WithFilesystemDriver;

    public function test_delete()
    {
        $this->assertNoContent(
            (new Request)
                ->delete()
                ->path($this->entityPath)
        );

        $this->assertFalse($this->getDisk()->exists('a1.txt'));
    }

    public function test_update_with_content()
    {
        $this->getDisk()->put('c1.txt', '');
        $this->assertJsonResponseSnapshot(
            (new Request)
                ->body([
                    '$value' => 'dGVzdA==',
                ])
                ->put()
                ->path($this->entitySetPath."('c1.txt')")
        );

        $this->assertMatchesTextSnapshot($this->getDisk()->get('c1.txt'));
    }
}