<?php

namespace Flat3\Lodata\Tests\Unit\Clients;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class PBIDS extends TestCase
{
    public function test_pbids()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/odata.pbids')
        );
    }
}