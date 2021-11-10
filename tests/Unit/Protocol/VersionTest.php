<?php

namespace Flat3\Lodata\Tests\Unit\Protocol;

use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class VersionTest extends TestCase
{
    public function test_has_standard_version_header()
    {
        $this->assertMetadataResponse(
            (new Request)
        );
    }

    public function test_rejects_bad_low_version_header()
    {
        $this->assertBadRequest(
            (new Request)
                ->header(Constants::odataVersion, '3.0')
        );
    }

    public function test_rejects_high_low_version_header()
    {
        $this->assertBadRequest(
            (new Request)
                ->header(Constants::odataVersion, '4.02')
        );
    }

    public function test_accepts_low_version_header()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->header(Constants::odataVersion, '4.0')
        );
    }

    public function test_accepts_maxversion_header()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->header(Constants::odataMaxVersion, '4.0')
        );
    }
}

