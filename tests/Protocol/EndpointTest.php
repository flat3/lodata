<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\TestCase;

class EndpointTest extends TestCase
{
    public function test_endpoint_url()
    {
        $this->assertEquals('http://localhost/odata/', Lodata::getEndpoint());
    }
}