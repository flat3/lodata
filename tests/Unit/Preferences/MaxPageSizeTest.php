<?php

namespace Flat3\OData\Tests\Unit\Preferences;

use Flat3\OData\Tests\Data\FlightModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class MaxPageSizeTest extends TestCase
{
    use FlightModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_uses_maxpagesize_preference()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/flights')
                ->header('Prefer', 'maxpagesize=1')
        );
    }

    public function test_uses_odata_maxpagesize_preference()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/flights')
                ->header('Prefer', 'odata.maxpagesize=1')
        );
    }
}
