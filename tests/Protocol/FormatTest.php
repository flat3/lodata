<?php

namespace Flat3\Lodata\Tests\Protocol;

use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;

class FormatTest extends TestCase
{
    public function test_rejects_bad_accept_subtype()
    {
        $this->assertNotAcceptable(
            (new Request)
                ->header('accept', 'application/text')
        );
    }

    public function test_no_accept_header()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->unsetHeader('accept')
        );
    }

    public function test_rejects_bad_accept_type_subtype()
    {
        $this->assertNotAcceptable(
            (new Request)
                ->header('accept', 'none/txt')
        );
    }

    public function test_accepts_type_subtype()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->header('accept', 'application/json')
        );
    }

    public function test_accepts_wildcard_type()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->header('accept', '*/json')
        );
    }

    public function test_accepts_full_wildcard()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->header('accept', '*/*')
        );
    }

    public function test_accepts_accept_parameters()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->header('accept', 'application/json;odata.metadata=full')
        );
    }

    public function test_accepts_multiple_accept_types()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->header('accept', 'application/json;q=1.0,application/xml;q=0.8')
        );
    }

    public function test_accepts_matching_fallback_accept_type()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->header('accept', 'application/xml;q=1.0,application/json;q=0.8')
        );
    }

    public function test_rejects_xml_on_service()
    {
        $this->assertNotAcceptable(
            (new Request)
                ->header('accept', 'application/xml')
        );
    }

    public function test_accepts_xml_subtype_on_metadata()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->header('accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8')
                ->path('/$metadata')
        );
    }

    public function test_prefers_json()
    {
        $this->assertJsonMetadataResponse(
            (new Request)
                ->header('accept', 'application/json,application/xml')
                ->path('/$metadata')
        );
    }

    public function test_prefers_xml()
    {
        $this->assertXmlResponseSnapshot(
            (new Request)
                ->header('accept', 'application/xml,application/json')
                ->path('/$metadata')
        );
    }

    public function test_accepts_xml_on_metadata()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->header('accept', 'application/xml')
                ->path('/$metadata')
        );
    }

    public function test_rejects_bad_format_subtype()
    {
        $this->assertNotAcceptable(
            (new Request)
                ->format('application/text')
        );
    }

    public function test_rejects_bad_format_type_subtype()
    {
        $this->assertNotAcceptable(
            (new Request)
                ->format('none/txt')
        );
    }

    public function test_accepts_format_type_subtype()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->format('application/json')
        );
    }

    public function test_accepts_format_wildcard_type()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->format('*/json')
        );
    }

    public function test_accepts_format_parameters()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->format('application/json;metadata=full')
        );
    }

    public function test_accepts_old_format_parameters()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->format('application/json;odata.metadata=full')
        );
    }

    public function test_accepts_format_short_json()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->format('json')
        );
    }

    public function test_rejects_short_format_with_parameters()
    {
        $this->assertBadRequest(
            (new Request)
                ->format('json;odata.metadata=none')
        );
    }

    public function test_rejects_format_short_xml()
    {
        $this->assertNotAcceptable(
            (new Request)
                ->format('xml')
        );
    }

    public function test_prioritises_format_query_option()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->header('accept', 'application/json;odata.metadata=none')
                ->format('application/json;odata.metadata=full')
        );
    }

    public function test_parses_format()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->header('accept', 'application/json;odata.metadata=full')
        );
    }

    public function test_advertises_formats()
    {
        $this->assertXmlResponseSnapshot(
            (new Request)
                ->xml()
                ->path('/$metadata')
        );
    }

    public function test_adds_charset_parameter()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->accept('application/json;charset=utf-8')
        );
    }

    public function test_adds_ieee754_parameter()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->accept('application/json;IEEE754Compatible=false')
        );
    }

    public function test_adds_ieee754_parameter_true()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->accept('application/json;IEEE754Compatible=true')
        );
    }

    public function test_adds_streaming_parameter()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->accept('application/json;streaming=true')
        );
    }

    public function test_adds_old_streaming_parameter()
    {
        $this->assertResponseSnapshot(
            (new Request)
                ->accept('application/json;odata.streaming=true')
        );
    }
}
