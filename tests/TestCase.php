<?php

namespace Flat3\OData\Tests;

use Flat3\OData\Exception\Protocol\BadRequestException;
use Flat3\OData\Exception\Protocol\NotAcceptableException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Flat3\OData\DataModel;
use Flat3\OData\Drivers\Database\Store;
use Flat3\OData\EntityType\Collection;
use Flat3\OData\Property;
use Flat3\OData\ServiceProvider;
use Flat3\OData\Tests\Models\Flight;
use Flat3\OData\Type\Int32;
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
        (new Flight([]))->save();
        $model = app()->make(DataModel::class);

        $entityType = new Collection('flight');
        $entityType->setKey(new Property('id', Int32::type()));

        $store = new Store('flights', $entityType);
        $store->setTable('flights');

        $model
            ->entityType($entityType)
            ->resource($store);
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

    protected function assertBadRequest(Request $request)
    {
        $this->expectException(BadRequestException::class);
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
