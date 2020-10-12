<?php

namespace Flat3\Lodata\Tests\Unit\Clients;

use Flat3\Lodata\Tests\Data\TestModels;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class ODCFF extends TestCase
{
    use TestModels;

    public function test_odcff()
    {
        $this->withFlightModel();
        $this->assertHtmlResponse(
            Request::factory()
                ->path('/airports.odc')
        );
    }

    public function test_odcff_missing()
    {
        $this->assertNotFound(
            Request::factory()
                ->path('/missing.odc')
        );
    }
}