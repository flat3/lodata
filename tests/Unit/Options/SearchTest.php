<?php

namespace Flat3\Lodata\Tests\Unit\Options;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class SearchTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_search()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->search('sfo')
        );
    }

    public function test_search_no_searchable_properties()
    {
        Lodata::getEntityType('airport')->getDeclaredProperty('code')->setSearchable(false);

        $this->assertInternalServerError(
            (new Request)
                ->path('/airports')
                ->search('sfo')
        );
    }

    public function test_search_not()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->search('NOT sfo')
        );
    }

    public function test_search_or()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->search('sfo OR lhr')
        );
    }

    public function test_search_and()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->search('sf AND sfo')
        );
    }

    public function test_search_invalid()
    {
        $this->assertBadRequest(
            (new Request)
                ->path('/airports')
                ->search('sf AND sfo OR')
        );
    }

    public function test_search_quote()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->search('"sfo "')
        );
    }

    public function test_search_paren()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->search('(sfo OR lax) OR lhr')
        );
    }
}

