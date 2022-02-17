<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

class ReadWriteTest extends TestCase
{
    use WithNumericCollectionDriver;

    public function setUp(): void
    {
        parent::setUp();

        config([
            'lodata.authorization' => false,
            'lodata.readonly' => true,
        ]);
    }

    public function test_readonly()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->path($this->entitySetPath)
        );
    }

    public function test_cannot_write()
    {
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

