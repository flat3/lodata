<?php

namespace Flat3\OData\Tests;

use Exception;
use Flat3\OData\DataModel;
use Flat3\OData\Drivers\Database\Store;
use Flat3\OData\EntityType\Collection;
use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Exception\Protocol\NoContentException;
use Flat3\OData\Exception\Protocol\NotAcceptableException;
use Flat3\OData\Exception\Protocol\NotFoundException;
use Flat3\OData\Property;
use Flat3\OData\ServiceProvider;
use Flat3\OData\Tests\Models\Airport;
use Flat3\OData\Tests\Models\Flight;
use Flat3\OData\Type\Int32;
use Flat3\OData\Type\String_;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Spatie\Snapshots\MatchesSnapshots;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use MatchesSnapshots;
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
    }

    public function withFlightDataModel(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../migrations');
        $this->artisan('migrate')->run();

        (new Flight([
            'origin' => 'lhr',
            'destination' => 'lax',
        ]))->save();

        (new Flight([
            'origin' => 'sam',
            'destination' => 'rgr',
        ]))->save();

        (new Airport([
            'code' => 'lhr',
            'name' => 'Heathrow',
        ]))->save();

        (new Airport([
            'code' => 'lax',
            'name' => 'Los Angeles',
        ]))->save();

        try {
            /** @var DataModel $model */
            $model = app()->make(DataModel::class);

            $flightType = new Collection('flight');
            $flightType->setKey(new Property('id', Int32::type()));
            $flightType->addProperty(new Property('origin', String_::type()));
            $flightType->addProperty(new Property('destination', String_::type()));
            $flightStore = new Store('flights', $flightType);
            $flightStore->setTable('flights');

            $airportType = new Collection('airport');
            $airportType->setKey(new Property('id', Int32::type()));
            $airportType->addProperty(new Property('name', String_::type()));
            $airportType->addProperty(new Property('code', String_::type()));
            $airportStore = new Store('airports', $airportType);
            $airportStore->setTable('airports');

            $model
                ->entityType($flightType)
                ->resource($flightStore);

            $model
                ->entityType($airportType)
                ->resource($airportStore);

            $nav = new Property\Navigation($airportStore, $airportType);
            $nav->addConstraint(new Property\Constraint($flightType->getProperty('origin'),
                $airportType->getProperty('code')));
            $flightType->addProperty($nav);
            $flightStore->addNavigationBinding(new Property\Navigation\Binding($nav, $airportStore));
        } catch (Exception $e) {
        }
    }

    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // perform environment setup
    }

    protected function assertNotAcceptable(Request $request)
    {
        $this->expectException(NotAcceptableException::class);
        $this->req($request);
    }

    protected function assertNotFound(Request $request)
    {
        $this->expectException(NotFoundException::class);
        $this->req($request);
    }

    protected function assertNoContent(Request $request)
    {
        $this->expectException(NoContentException::class);
        $this->req($request);
    }

    public function req(Request $request)
    {
        return $this->call(
            'GET',
            'odata'.$request->uri(),
            [],
            [],
            [],
            $this->transformHeadersToServerVars($request->headers)
        );
    }

    protected function assertBadRequest(Request $request)
    {
        $this->expectException(BadRequestException::class);
        $this->req($request);
    }

    protected function assertXmlResponse(Request $request)
    {
        $response = $this->req($request);
        $this->assertMatchesXmlSnapshot($response->streamedContent());
        $this->assertResponseMetadata($response);
    }

    protected function assertResponseMetadata(TestResponse $response)
    {
        $this->assertMatchesSnapshot([
            'headers' => array_diff_key($response->baseResponse->headers->all(), array_flip(['date'])),
            'status' => $response->baseResponse->getStatusCode(),
        ]);
    }

    protected function assertMetadataResponse(Request $request)
    {
        $response = $this->req($request);
        $this->assertResponseMetadata($response);
    }

    protected function assertJsonResponse(Request $request)
    {
        $response = $this->req($request);
        $this->assertMatchesSnapshot($response->streamedContent(), new JsonDriver());
        $this->assertResponseMetadata($response);
    }

    protected function assertTextResponse(Request $request)
    {
        $response = $this->req($request);
        $this->assertMatchesTextSnapshot($response->streamedContent());
        $this->assertResponseMetadata($response);
    }
}
