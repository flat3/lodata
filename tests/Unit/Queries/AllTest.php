<?php

namespace Flat3\Lodata\Tests\Unit\Queries;

use Flat3\Lodata\Drivers\StaticEntitySet;
use Flat3\Lodata\EntityType;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MetadataType;

class AllTest extends TestCase
{
    public function test_read_all()
    {
        $this->withFlightModel();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/$all')
        );
    }

    public function test_read_all_select()
    {
        $this->withFlightModel();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/$all')
                ->query('$select', 'id')
        );
    }

    public function test_read_all_orderby()
    {
        $this->withFlightModel();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/$all')
                ->query('$orderby', 'id desc')
        );
    }

    public function test_read_all_metadata()
    {
        $this->withFlightModel();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/$all')
                ->metadata(MetadataType\Full::name)
        );
    }

    public function test_read_all_singleton()
    {
        $this->withSingleton();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/$all')
        );
    }

    public function test_read_all_singleton_metadata()
    {
        $this->withSingleton();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/$all')
                ->metadata(MetadataType\Full::name)
        );
    }

    public function test_read_all_type()
    {
        $this->withFlightModel();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/$all/flight')
        );
    }

    public function test_bad_type()
    {
        $this->assertBadRequest(
            Request::factory()
                ->path('/$all/flight')
        );
    }

    public function test_read_empty()
    {
        Lodata::add(new StaticEntitySet(new EntityType('basic')));

        $this->assertJsonResponse(
            Request::factory()
                ->path('/$all')
        );
    }
}