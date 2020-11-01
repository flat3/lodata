<?php

namespace Flat3\Lodata\Tests\Unit\Clients;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class PBIDSTest extends TestCase
{
    public function test_pbids()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/_lodata/odata.pbids')
        );
    }

    public function test_pbids_url()
    {
        $this->assertEquals('http://localhost/odata/_lodata/odata.pbids', Lodata::getPbidsUrl());
    }
}