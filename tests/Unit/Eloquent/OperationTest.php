<?php

namespace Flat3\Lodata\Tests\Unit\Eloquent;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Models\Airport;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class OperationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped();
        }

        $this->withFlightDatabase();
        Lodata::discover(Airport::class);
    }

    public function test_op1()
    {
        $this->withFlightData();

        $this->assertJsonResponse(
            (new Request)
                ->path('/Airports/1/op1')
        );
    }

    public function test_op2()
    {
        $this->withFlightData();

        $this->assertJsonResponse(
            (new Request)
                ->path("/Airports/1/op2(prefix='o')")
        );
    }
}