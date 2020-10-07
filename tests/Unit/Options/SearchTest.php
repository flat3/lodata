<?php

namespace Flat3\OData\Tests\Unit\Options;

use Flat3\OData\Model;
use Flat3\OData\Tests\Data\TestModels;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

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
            Request::factory()
                ->path('/airports')
                ->query('$search', 'sfo')
        );
    }

    public function test_search_no_searchable_properties()
    {
        Model::get()->getEntityTypes()->get('airport')->getProperty('code')->setSearchable(false);

        $this->assertInternalServerError(
            Request::factory()
                ->path('/airports')
                ->query('$search', 'sfo')
        );
    }

    public function test_search_not()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$search', 'NOT sfo')
        );
    }

    public function test_search_or()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$search', 'sfo OR lhr')
        );
    }

    public function test_search_and()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$search', 'sf AND sfo')
        );
    }

    public function test_search_invalid()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/airports')
                ->query('$search', 'sf AND sfo OR')
        );
    }

    public function test_search_quote()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$search', '"sfo "')
        );
    }

    public function test_search_paren()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/airports')
                ->query('$search', '(sfo OR lax) OR lhr')
        );
    }
}

