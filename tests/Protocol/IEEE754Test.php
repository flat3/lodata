<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

class IEEE754Test extends TestCase
{
    use WithNumericCollectionDriver;

    public function test_ieee754_response()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->header('accept', 'application/json;IEEE754Compatible=true')
                ->path($this->entityPath)
        );
    }

    public function test_no_ieee754_response()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->header('accept', 'application/json;IEEE754Compatible=false')
                ->path($this->entityPath)
        );
    }
}