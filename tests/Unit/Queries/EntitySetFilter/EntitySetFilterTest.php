<?php

namespace Flat3\Lodata\Tests\Unit\Queries\EntitySetFilter;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class EntitySetFilterTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_path_filter()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path("/airports/\$filter(code eq 'lhr')")
        );
    }

    public function test_path_query_filter()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path("/airports/\$filter(is_big eq true)")
                ->filter("code eq 'lhr'")
        );
    }

    public function test_path_query_filter_search()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path("/airports/\$filter(is_big eq true)")
                ->filter("code eq 'lhr' or code eq 'ohr'")
                ->search('lh')
        );
    }

    public function test_path_filter_no_argument()
    {
        $this->assertBadRequest(
            (new Request)
                ->path("\$filter(code eq 'lhr')")
        );
    }

    public function test_path_query_filter_segment_and_param()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path("/airports/\$filter(@ib)")
                ->query('@ib', 'is_big eq true')
                ->filter("code eq 'lhr'")
        );
    }

    public function test_path_query_filter_segment_multiple()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports/$filter(@a)/$filter(@b)')
                ->query('@a', "code eq 'lhr'")
                ->query('@b', 'is_big eq true')
        );
    }

    public function test_path_query_filter_segment_multiple_count()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports/$filter(@a)/$filter(@b)/$count')
                ->text()
                ->query('@a', "code eq 'lhr'")
                ->query('@b', 'is_big eq true')
        );
    }
}
