<?php

namespace Flat3\Lodata\Tests\Unit\Protocol;

use Flat3\Lodata\EntityType;
use Flat3\Lodata\Model;
use Flat3\Lodata\Tests\Models\Airport as AirportEModel;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class UrlTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightModel();

        (new AirportEModel([
            'code' => 'air',
            'name' => "Air/Port",
            'construction_date' => '1099-01-01',
            'open_time' => '11:00:00',
            'sam_datetime' => '1999-11-10T14:00:01+00:00',
            'is_big' => false,
        ]))->save();

        /** @var EntityType $airportType */
        $airportType = Model::get()->getEntityTypes()->get('airport');
        $airportType->getProperty('name')->setSearchable()->setAlternativeKey();
        $airportType->getProperty('code')->setSearchable()->setAlternativeKey();
    }

    public function test_valid_1()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path("/airports(name='O''Hare')")
        );
    }

    public function test_valid_2()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path("/airports(name%3D%27O%27%27Hare%27)")
        );
    }

    public function test_valid_3()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path("/airports%28name%3D%27O%27%27Hare%27%29")
        );
    }

    public function test_valid_4()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path("/airports(name='Air%2FPort')")
        );
    }

    public function test_invalid_urls_1()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path("/airports('O'Hare')")
        );
    }

    public function test_invalid_urls_2()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path("/airports(name='O%27Hare')")
        );
    }

    public function test_invalid_urls_3()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path("/airports('Air/Port')")
        );
    }
}
