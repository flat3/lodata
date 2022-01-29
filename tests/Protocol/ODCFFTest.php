<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

class ODCFFTest extends TestCase
{
    use WithNumericCollectionDriver;

    public function test_odcff()
    {
        $this->assertHtmlResponseSnapshot(
            (new Request)
                ->path('/_lodata/passengers.odc')
        );
    }

    public function test_odcff_missing()
    {
        $this->expectException(NotFoundException::class);
        $this->req(
            (new Request)
                ->path('/_lodata/missing.odc')
        );
    }

    public function test_odcff_url()
    {
        $this->assertEquals('http://localhost/odata/_lodata/passengers.odc', Lodata::getOdcUrl('passengers'));
    }
}