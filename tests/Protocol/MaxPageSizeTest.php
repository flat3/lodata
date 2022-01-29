<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

class MaxPageSizeTest extends TestCase
{
    use WithNumericCollectionDriver;

    public function test_uses_maxpagesize_preference()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->path($this->entitySetPath)
                ->header('Prefer', 'maxpagesize=1')
        );
    }

    public function test_uses_odata_maxpagesize_preference()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->path($this->entitySetPath)
                ->header('Prefer', 'odata.maxpagesize=1')
        );
    }
}
