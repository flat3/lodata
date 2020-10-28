<?php

namespace Flat3\Lodata\Tests\Unit\Queries\Expand;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

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
            Request::factory()
                ->path('/flights')
                ->query('$expand', 'passengers')
        );
    }

    public function test_expand_property()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)/passengers')
        );
    }

    public function test_expand_and_select()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->query('$expand', 'passengers')
                ->query('$select', 'origin')
        );
    }

    public function test_select_with_expand_select()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->query('$select', 'origin,destination')
                ->query('$expand', 'passengers($select=name)')
        );
    }

    public function test_expand_containing_filter()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->query('$expand', "passengers(\$filter=startswith(name, 'Bob'))")
        );
    }

    public function test_expand_containing_select()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->query('$expand', 'passengers($select=name)')
        );
    }

    public function test_expand_containing_orderby()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->query('$expand', 'passengers($orderby=name desc)')
        );
    }

    public function test_expand_containing_skip()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->query('$expand', 'passengers($skip=1)')
        );
    }

    public function test_expand_containing_top()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->query('$expand', 'passengers($top=2)')
        );
    }

    public function test_expand_containing_search()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->query('$expand', 'passengers($search=Bar)')
        );
    }

    public function test_expand_containing_orderby_select()
    {
        $this->markTestIncomplete();
        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)')
                ->query('$expand', 'passengers($orderby=name desc,$select=name)')
        );
    }
}
