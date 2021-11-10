<?php

namespace Flat3\Lodata\Tests\Unit\Preferences;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class OmitValuesTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_uses_omitvalues_nulls_preference()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->path('/flights(2)')
                ->header('Prefer', 'omit-values=nulls')
        );
    }
}
