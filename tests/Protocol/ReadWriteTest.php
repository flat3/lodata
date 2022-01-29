<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

class ReadWriteTest extends TestCase
{
    use WithNumericCollectionDriver;

    public function test_readonly()
    {
        config(['lodata.readonly' => true]);

        $this->assertResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
        );
    }

    public function test_cannot_write()
    {
        config(['lodata.readonly' => true]);

        $this->assertForbidden(
            (new Request)
                ->path($this->entitySetPath)
                ->post()
                ->body([
                    'origin' => 'lhr',
                ])
        );
    }
}

