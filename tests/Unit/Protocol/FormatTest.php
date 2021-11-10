<?php

namespace Flat3\Lodata\Tests\Unit\Protocol;

use Flat3\Lodata\Tests\Request;
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

    public function test_rejects_bad_accept_type_subtype()
    {
        $this->assertNotAcceptable(
            (new Request)
                ->header('accept', 'none/txt')
        );
    }

    public function test_accepts_type_subtype()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->header('accept', 'application/json')
        );
    }

    public function test_accepts_wildcard_type()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->header('accept', '*/json')
        );
    }

    public function test_accepts_full_wildcard()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->header('accept', '*/*')
        );
    }

    public function test_accepts_accept_parameters()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->header('accept', 'application/json;odata.metadata=full')
        );
    }

    public function test_accepts_multiple_accept_types()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->header('accept', 'application/json;q=1.0,application/xml;q=0.8')
        );
    }

    public function test_accepts_matching_fallback_accept_type()
    {
        $this->assertMetadataResponse(
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

    public function test_accepts_xml_on_metadata()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->header('accept', 'application/xml')
                ->path('/$metadata')
        );
    }

    public function test_rejects_bad_format_subtype()
    {
        $this->assertNotAcceptable(
            (new Request)
                ->query('$format', 'application/text')
        );
    }

    public function test_rejects_bad_format_type_subtype()
    {
        $this->assertNotAcceptable(
            (new Request)
                ->query('$format', 'none/txt')
        );
    }

    public function test_accepts_format_type_subtype()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->query('$format', 'application/json')
        );
    }

    public function test_accepts_format_wildcard_type()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->query('$format', '*/json')
        );
    }

    public function test_accepts_format_parameters()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->query('$format', 'application/json;metadata=full')
        );
    }

    public function test_accepts_old_format_parameters()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->query('$format', 'application/json;odata.metadata=full')
        );
    }

    public function test_accepts_format_short_json()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->query('$format', 'json')
        );
    }

    public function test_rejects_short_format_with_parameters()
    {
        $this->assertBadRequest(
            (new Request)
                ->query('$format', 'json;odata.metadata=none')
        );
    }

    public function test_rejects_format_short_xml()
    {
        $this->assertNotAcceptable(
            (new Request)
                ->query('$format', 'xml')
        );
    }

    public function test_prioritises_format_query_option()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->header('accept', 'application/json;odata.metadata=none')
                ->query('$format', 'application/json;odata.metadata=full')
        );
    }

    public function test_parses_format()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->header('accept', 'application/json;odata.metadata=full')
        );
    }

    public function test_advertises_formats()
    {
        $this->assertXmlResponse(
            (new Request)
                ->xml()
                ->path('/$metadata')
        );
    }

    public function test_adds_charset_parameter()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->accept('application/json;charset=utf-8')
        );
    }

    public function test_adds_ieee754_parameter()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->accept('application/json;IEEE754Compatible=false')
        );
    }

    public function test_adds_ieee754_parameter_true()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->accept('application/json;IEEE754Compatible=true')
        );
    }

    public function test_adds_streaming_parameter()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->accept('application/json;streaming=true')
        );
    }

    public function test_adds_old_streaming_parameter()
    {
        $this->assertMetadataResponse(
            (new Request)
                ->accept('application/json;odata.streaming=true')
        );
    }
}
