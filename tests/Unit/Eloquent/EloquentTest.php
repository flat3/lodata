<?php

namespace Flat3\Lodata\Tests\Unit\Eloquent;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Drivers\EloquentEntitySet;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Tests\Models\Airport;
use Flat3\Lodata\Tests\Models\Country;
use Flat3\Lodata\Tests\Models\Flight;
use Flat3\Lodata\Tests\Models\Passenger;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Transaction\MetadataType;
use Flat3\Lodata\Type;

class EloquentTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightDatabase();

        $airports = Lodata::discoverEloquentModel(Airport::class);
        $flights = Lodata::discoverEloquentModel(Flight::class);
        $countries = Lodata::discoverEloquentModel(Country::class);
        $passengers = Lodata::discoverEloquentModel(Passenger::class);
        Lodata::getEntityType('Flight')->getProperty('duration')->setType(Type::duration());

        $airports->discoverRelationship('flights');
        $airports->discoverRelationship('country');
        $flights->discoverRelationship('passengers');
        $passengers->discoverRelationship('flight');
        $countries->discoverRelationship('airports');
        $passengers->discoverRelationship('originAirport');
        $passengers->discoverRelationship('destinationAirport');
        $flights->discoverRelationship('originAirport');
        $flights->discoverRelationship('destinationAirport');

        $airport = Lodata::getEntityType('Airport');
        $airport->getDeclaredProperty('code')->setAlternativeKey();
    }

    public function test_failed_relationship()
    {
        /** @var EloquentEntitySet $countries */
        $countries = Lodata::getEntitySet('Countries');

        try {
            $countries->discoverRelationship('airport');
        } catch (InternalServerErrorException $e) {
            $this->assertProtocolExceptionSnapshot($e);
        }
    }

    public function test_metadata()
    {
        $this->assertXmlResponse(
            Request::factory()
                ->path('/$metadata')
                ->xml()
        );
    }

    public function test_set()
    {
        $this->withFlightData();

        $this->assertJsonResponse(
            Request::factory()
                ->text()
                ->path('/Airports/$count')
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

        $this->assertDatabaseSnapshot();
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
                ->path('/Airports'),
            Response::HTTP_CREATED
        );

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Airports(1)')
        );

        $this->assertDatabaseSnapshot();
    }

    public function test_create_deep()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/Flights')
                ->post()
                ->body([
                    'origin' => 'lhr',
                    'destination' => 'sfo',
                    'passengers' => [
                        [
                            'name' => 'Alice',
                        ],
                        [
                            'name' => 'Bob',
                        ],
                    ],
                ]),
            Response::HTTP_CREATED
        );

        $this->assertDatabaseSnapshot();
    }

    public function test_create_navigation_property()
    {
        $this->withFlightData();
        $this->assertJsonResponse(
            Request::factory()
                ->post()
                ->body([
                    'name' => 'Harry Horse',
                ])
                ->path('/Flights(1)/passengers'),
            Response::HTTP_CREATED
        );

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Flights(1)/passengers')
        );

        $this->assertDatabaseSnapshot();
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

        $this->assertDatabaseSnapshot();
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

    public function test_query_count()
    {
        $model = new Airport();
        $model['name'] = 'Eloquent';
        $model['code'] = 'elo';
        $model->save();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Airports/$count')
                ->text()
                ->filter("code eq 'elo'")
        );
    }

    public function test_select()
    {
        $model = new Airport();
        $model['name'] = 'Eloquent';
        $model['code'] = 'elo';
        $model->save();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Airports')
                ->select("code")
        );
    }

    public function test_orderby()
    {
        $ap1 = new Airport();
        $ap1['name'] = 'Eloquent';
        $ap1['code'] = 'elo';
        $ap1->save();

        $ap2 = new Airport();
        $ap2['name'] = 'Eloquint';
        $ap2['code'] = 'eli';
        $ap2->save();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Airports')
                ->query('$orderby', 'code asc')
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
                ->query('$expand', 'flights')
        );
    }

    public function test_expand_entity()
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
                ->path('/Airports(1)')
                ->query('$expand', 'flights')
        );
    }

    public function test_expand_hasone_entity()
    {
        $co1 = new Country();
        $co1['name'] = 'en';
        $co1->save();

        $ap1 = new Airport();
        $ap1['name'] = 'Eloquent';
        $ap1['code'] = 'elo';
        $ap1['country_id'] = 1;
        $ap1->save();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Airports(1)')
                ->query('$expand', 'country')
        );
    }

    public function test_expand_hasone_entity_property()
    {
        $co1 = new Country();
        $co1['name'] = 'en';
        $co1->save();

        $ap1 = new Airport();
        $ap1['name'] = 'Eloquent';
        $ap1['code'] = 'elo';
        $ap1['country_id'] = 1;
        $ap1->save();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Airports(1)/country')
        );
    }

    public function test_expand_hasmany_entity()
    {
        $this->withFlightData();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Flights(1)')
                ->query('$expand', 'passengers')
        );
    }

    public function test_expand_hasmany_entity2()
    {
        $this->withFlightData();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Flights(2)')
                ->query('$expand', 'passengers')
        );
    }

    public function test_expand_hasmany_entity_expand()
    {
        $this->withFlightData();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Flights(2)')
                ->query('$expand', 'passengers($expand=flight)')
        );
    }

    public function test_expand_hasmany_entity_expand_full_metadata()
    {
        $this->withFlightData();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Flights')
                ->metadata(MetadataType\Full::name)
                ->query('$expand', 'passengers($expand=flight)')
        );
    }

    public function test_expand_belongsto_entity()
    {
        $this->withFlightData();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Passengers(1)')
                ->query('$expand', 'flight')
        );
    }

    public function test_expand_belongsto_property()
    {
        $this->withFlightData();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Passengers(1)/flight')
        );
    }

    public function test_expand_belongsto_property_select_nonexistent_property()
    {
        $this->withFlightData();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Flights(1)/passengers')
                ->query('$select', 'naame')
        );
    }

    public function test_expand_belongsto_property_select_existent_property()
    {
        $this->withFlightData();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Flights(1)/passengers')
                ->query('$select', 'name')
        );
    }

    public function test_select_existent_property_full_metadata_ignoring_nulls()
    {
        $this->withFlightData();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Flights')
                ->metadata(MetadataType\Full::name)
                ->preference('omit-values', 'nulls')
                ->query('$select', 'gate')
        );
    }

    public function test_expand_belongsto_property_full_metadata()
    {
        $this->withFlightData();

        $this->assertJsonResponse(
            Request::factory()
                ->metadata(MetadataType\Full::name)
                ->path('/Passengers(1)/flight')
        );
    }

    public function test_expand_property_invalid_entity()
    {
        $this->withFlightData();

        $this->assertNotFound(
            Request::factory()
                ->path('/Countries/airports')
        );
    }

    public function test_expand_property_entity()
    {
        $co1 = new Country();
        $co1['name'] = 'en';
        $co1->save();

        $ap1 = new Airport();
        $ap1['name'] = 'Eloquent';
        $ap1['code'] = 'elo';
        $ap1['country_id'] = 1;
        $ap1->save();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Countries')
                ->query('$expand', 'airports')
        );
    }

    public function test_expand_hasonethrough()
    {
        $this->withFlightData();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Passengers')
                ->query('$expand', 'originAirport,destinationAirport')
        );
    }

    public function test_expand_hasone()
    {
        $this->withFlightData();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Flights(1)')
                ->query('$expand', 'originAirport,destinationAirport')
        );
    }

    public function test_expand_hasone_property()
    {
        $this->withFlightData();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Flights(1)/originAirport')
        );

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Flights(3)/destinationAirport')
        );

        $this->assertNoContent(
            Request::factory()
                ->path('/Flights(2)/destinationAirport')
        );

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Flights(3)/destinationAirport/code')
        );

        $this->assertTextResponse(
            Request::factory()
                ->text()
                ->path('/Flights(3)/destinationAirport/code/$value')
        );
    }

    public function test_expand_property()
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
                ->path('/Airports(1)/flights')
        );
    }

    public function test_expand_property_count()
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
                ->text()
                ->path('/Airports(1)/flights/$count')
        );
    }

    public function test_compound_filter()
    {
        $this->withFlightData();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Airports')
                ->filter("startswith(code, 'l') and is_big eq true")
        );
    }

    public function test_read_with_select_and_metadata()
    {
        $this->withFlightData();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Flights')
                ->metadata(MetadataType\Full::name)
                ->query('$select', 'destination')
        );
    }

    public function test_deep_transaction_failed()
    {
        $this->withFlightData();

        $this->captureDatabaseState();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/Flights')
                ->post()
                ->body([
                    'origin' => 'lhr',
                    'destination' => 'sfo',
                    'passengers' => [
                        [
                            'name' => 'Alice',
                        ],
                        [
                        ],
                    ],
                ]),
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        $this->assertDatabaseMatchesCapturedState();
    }
}