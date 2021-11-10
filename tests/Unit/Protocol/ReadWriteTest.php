<?php

namespace Flat3\Lodata\Tests\Unit\Protocol;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class ReadWriteTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_readonly()
    {
        config(['lodata.readonly' => true]);

        $this->assertMetadataResponse(
            (new Request)
                ->path('/flights')
        );
    }

    public function test_cannot_write()
    {
        config(['lodata.readonly' => true]);

        $this->assertForbidden(
            (new Request)
                ->path('/flights')
                ->post()
                ->body([
                    'origin' => 'lhr',
                ])
        );
    }
}

