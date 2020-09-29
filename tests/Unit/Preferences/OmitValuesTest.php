<?php

namespace Flat3\OData\Tests\Unit\Preferences;

use Flat3\OData\Tests\Data\FlightDataModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class OmitValuesTest extends TestCase
{
    use FlightDataModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightDataModel();
    }

    public function test_uses_omitvalues_nulls_preference()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
                ->path('/flights(2)')
                ->header('Prefer', 'omit-values=nulls')
        );
    }
}
