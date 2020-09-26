<?php

namespace Flat3\OData\Tests\Unit\Queries\Types;

use Flat3\OData\Tests\Request;

class StringTest extends TypeTest
{

    public function test_filter_string_eq()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights')
                ->filter("origin eq 'lhr'")
                ->select('id,origin')
        );
    }

    public function test_filter_string_ne()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights')
                ->filter("origin ne 'lhr'")
                ->select('id,origin')
        );
    }
}

