<?php

namespace Flat3\OData\Tests\Unit\Protocol;

use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;
use Flat3\OData\Transaction\Version;

class VersionTest extends TestCase
{
    public function test_has_standard_version_header()
    {
        $this->assertMetadataResponse(
            Request::factory()
        );
    }

    public function test_rejects_bad_low_version_header()
    {
        $this->assertBadRequest(
            Request::factory()
                ->header(Version::versionHeader, '3.0')
        );
    }

    public function test_rejects_high_low_version_header()
    {
        $this->assertBadRequest(
            Request::factory()
                ->header(Version::versionHeader, '4.02')
        );
    }

    public function test_accepts_low_version_header()
    {
        $this->assertMetadataResponse(
            Request::factory()
                ->header(Version::versionHeader, '4.0')
        );
    }

    public function test_accepts_maxversion_header()
    {
        $this->assertMetadataResponse(
            Request::factory()
                ->header(Version::maxVersionHeader, '4.0')
        );
    }
}

