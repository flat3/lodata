<?php

namespace Flat3\Lodata\Tests\Unit\Queries\Expand;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MetadataType;

class ExpandTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_expand()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/flights')
                ->expand('passengers')
        );
    }

    public function test_expand_full_metadata()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/flights')
                ->metadata(MetadataType\Full::name)
                ->expand('passengers')
        );
    }

    public function test_expand_full_metadata_count()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/flights')
                ->metadata(MetadataType\Full::name)
                ->expand('passengers($count=true)')
        );
    }

    public function test_expand_full_metadata_top()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/flights')
                ->metadata(MetadataType\Full::name)
                ->expand('passengers($count=true;$top=1)')
        );
    }

    public function test_expand_property()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/flights(1)/passengers')
        );
    }

    public function test_expand_and_select()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/flights(1)')
                ->expand('passengers')
                ->select('origin')
        );
    }

    public function test_select_with_expand_select()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/flights(1)')
                ->select('origin,destination')
                ->expand('passengers($select=name)')
        );
    }

    public function test_select_with_expand_select_multiple()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/flights(1)')
                ->select('origin')
                ->expand('airports($select=name,review_score,construction_date)')
        );
    }

    public function test_select_with_expand_select_multiple_and_top()
    {
        $page = $this->getResponseBody(
            $this->assertJsonResponse(
                (new Request)
                    ->path('/flights(1)')
                    ->select('origin')
                    ->expand('passengers($select=flight_id,name;$top=2)')
            )
        );

        $this->assertJsonResponse(
            $this->urlToReq($page->{'passengers@nextLink'})
        );
    }

    public function test_expand_containing_filter()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/flights(1)')
                ->expand("passengers(\$filter=startswith(name, 'Bob'))")
        );
    }

    public function test_expand_containing_select()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/flights(1)')
                ->expand('passengers($select=name)')
        );
    }

    public function test_expand_containing_orderby()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/flights(1)')
                ->expand('passengers($orderby=name desc)')
        );
    }

    public function test_expand_containing_skip()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/flights(1)')
                ->expand('passengers($skip=1)')
        );
    }

    public function test_expand_containing_top()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/flights(1)')
                ->expand('passengers($top=2)')
        );
    }

    public function test_expand_containing_search()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/flights(1)')
                ->expand('passengers($search=Bar)')
        );
    }

    public function test_expand_containing_orderby_select()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/flights(1)')
                ->expand('passengers($orderby=name desc;$select=name)')
        );
    }

    public function test_expand_containing_function_filter_select()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/flights(1)')
                ->expand('passengers($filter=startswith(name, \'Bob\');$select=name)')
        );
    }
}
