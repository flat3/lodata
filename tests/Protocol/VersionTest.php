<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Helper\Constants;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\Version;

class VersionTest extends TestCase
{
    public function test_has_standard_version_header()
    {
        $this->assertResponseSnapshot(
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
        $this->assertResponseSnapshot(
            (new Request)
                ->header(Constants::odataVersion, '4.0')
        );
    }

    public function test_accepts_maxversion_header()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->header(Constants::odataMaxVersion, '4.0')
        );
    }

    public function test_config_default_version()
    {
        config(['lodata.version' => Version::v4_0]);

        $this->assertResponseSnapshot((new Request));
    }
}

