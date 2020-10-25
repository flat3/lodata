<?php

namespace Flat3\Lodata\Tests\Unit\Eloquent;

use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Models\Airport;
use Flat3\Lodata\Tests\Models\Flight;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;

class EloquentTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightDatabase();

        $airports = Lodata::discoverEloquentModel(Airport::class);
        Lodata::discoverEloquentModel(Flight::class);
        $airports->discoverRelationship('flights');

        $airport = Lodata::getEntityType('Airport');
        $airport->getProperty('code')->setAlternativeKey();
    }

    public function test_metadata()
    {
        $this->assertXmlResponse(
            Request::factory()
                ->path('/$metadata')
                ->xml()
        );
    }

    public function test_read()
    {
        $model = new Airport();
        $model['name'] = 'Eloquent';
        $model['code'] = 'elo';
        $model->save();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Airports(1)')
        );
    }

    public function test_read_alternative_key()
    {
        $model = new Airport();
        $model['name'] = 'Eloquent';
        $model['code'] = 'elo';
        $model->save();

        $this->assertJsonResponse(
            Request::factory()
                ->path("/Airports(code='elo')")
        );
    }

    public function test_update()
    {
        $model = new Airport();
        $model['name'] = 'Eloquent';
        $model['code'] = 'elo';
        $model->save();

        $this->assertJsonResponse(
            Request::factory()
                ->patch()
                ->body([
                    'code' => 'efo',
                ])
                ->path('/Airports(1)')
        );

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Airports(1)')
        );
    }

    public function test_create()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->post()
                ->body([
                    'code' => 'efo',
                    'name' => 'Eloquent',
                ])
                ->path('/Airports')
        );

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Airports(1)')
        );
    }

    public function test_delete()
    {
        $model = new Airport();
        $model['name'] = 'Eloquent';
        $model['code'] = 'elo';
        $model->save();

        $this->assertNoContent(
            Request::factory()
                ->delete()
                ->path('/Airports(1)')
        );

        $this->assertNotFound(
            Request::factory()
                ->path('/Airports(1)')
        );
    }

    public function test_query()
    {
        $model = new Airport();
        $model['name'] = 'Eloquent';
        $model['code'] = 'elo';
        $model->save();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Airports')
                ->filter("code eq 'elo'")
        );
    }

    public function test_expand()
    {
        $ap1 = new Airport();
        $ap1['name'] = 'Eloquent';
        $ap1['code'] = 'elo';
        $ap1->save();

        $ap2 = new Airport();
        $ap2['name'] = 'Eloquint';
        $ap2['code'] = 'eli';
        $ap2->save();

        $fl1 = new Flight();
        $fl1['origin'] = 'elo';
        $fl1->save();

        $fl2 = new Flight();
        $fl2['origin'] = 'elo';
        $fl2->save();

        $fl3 = new Flight();
        $fl3['origin'] = 'eli';
        $fl3->save();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Airports')
                ->query('$expand', 'Flights')
        );
    }
}