<?php

namespace Flat3\OData\Tests\Unit;

use Flat3\OData\Attribute\Version;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class ProtocolTest extends TestCase
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

    public function test_rejects_bad_accept_subtype()
    {
        $this->assertNotAcceptable(
            Request::factory()
                ->header('accept', 'application/text')
        );
    }

    public function test_rejects_bad_accept_type_subtype() {
        $this->assertNotAcceptable(
            Request::factory()
            ->header('accept', 'none/txt')
        );
    }

    public function test_accepts_type_subtype() {
        $this->assertMetadataResponse(
            Request::factory()
            ->header('accept', 'application/json')
        );
    }

    public function test_accepts_wildcard_type() {
        $this->assertMetadataResponse(
            Request::factory()
            ->header('accept', '*/json')
        );
    }

    public function test_accepts_accept_parameters() {
        $this->assertMetadataResponse(
            Request::factory()
            ->header('accept', 'application/json;metadata=full')
        );
    }

    public function test_rejects_xml_on_service() {
        $this->assertNotAcceptable(
            Request::factory()
            ->header('accept', 'application/xml')
        );
    }

    public function test_accepts_xml_on_metadata() {
        $this->assertMetadataResponse(
            Request::factory()
            ->header('accept', 'application/xml')
            ->path('$metadata')
        );
    }

    public function test_rejects_bad_format_subtype() {
        $this->assertNotAcceptable(
            Request::factory()
            ->query('$format', 'application/text')
        );
    }

    public function test_rejects_bad_format_type_subtype() {
        $this->assertNotAcceptable(
            Request::factory()
            ->query('$format', 'none/txt')
        );
    }

    public function test_accepts_format_type_subtype() {
        $this->assertMetadataResponse(
            Request::factory()
            ->query('$format', 'application/json')
        );
    }
    public function test_accepts_format_wildcard_type() {
        $this->assertMetadataResponse(
            Request::factory()
            ->query('$format', '*/json')
        );
    }

    public function test_accepts_format_parameters() {
        $this->assertMetadataResponse(
            Request::factory()
            ->query('$format', 'application/json;metadata=full')
        );
    }

    public function test_accepts_format_short_json() {
        $this->assertMetadataResponse(
            Request::factory()
            ->query('$format', 'json')
        );
    }

    public function test_rejects_format_short_xml() {
        $this->assertNotAcceptable(
            Request::factory()
            ->query('$format', 'xml')
        );
    }

    public function test_prioritises_format_query_option() {
        $this->assertMetadataResponse(
            Request::factory()
            ->header('accept', 'application/json;odata.metadata=none')
            ->query('$format', 'application/json;odata.metadata=full')
        );
    }

    public function test_parses_format() {
        $this->assertMetadataResponse(
            Request::factory()
            ->header('accept', 'application/json;odata.metadata=full')
        );
    }
}
