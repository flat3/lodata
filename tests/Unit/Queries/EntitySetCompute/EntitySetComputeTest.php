<?php

namespace Flat3\Lodata\Tests\Unit\Queries\EntitySetCompute;

use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class EntitySetComputeTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();
    }

    public function test_simple()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path("/airports")
                ->compute("concat('hello','world') as helloworld")
        );
    }

    public function test_field()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path("/airports")
                ->compute("concat(code,' world') as helloworld")
        );
    }

    public function test_record()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path("/airports/1")
                ->compute("concat(code,' world') as helloworld")
        );
    }

    public function test_record_key()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path("/airports(1)")
                ->compute("concat(code,' world') as helloworld")
        );
    }

    public function test_compute_set()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->compute("concat(code, ' test') as testprop")
        );
    }

    public function test_compute_single()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports/1')
                ->compute("concat(code, ' test') as testprop")
        );
    }

    public function test_compute_complex()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports/1')
                ->compute("year(construction_date) add month(sam_datetime) as testprop")
        );
    }

    public function test_compute_orderby()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->compute("year(sam_datetime) add month(sam_datetime) add day(sam_datetime) add length(name) as testprop")
                ->orderby('testprop desc')
        );
    }

    public function test_compute_filter()
    {
        $this->assertJsonResponse(
            (new Request)
                ->path('/airports')
                ->compute("year(sam_datetime) add month(sam_datetime) add day(sam_datetime) add length(name) as testprop")
                ->filter('(testprop add 10) gt 2040')
        );
    }
}