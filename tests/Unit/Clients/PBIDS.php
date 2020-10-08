<?php

namespace Flat3\OData\Tests\Unit\Clients;

use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

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