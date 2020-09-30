<?php

namespace Flat3\OData\Tests\Unit\Operation;

use Flat3\OData\DataModel;
use Flat3\OData\Entity;
use Flat3\OData\EntitySet;
use Flat3\OData\EntityType;
use Flat3\OData\Operation\Function_;
use Flat3\OData\Tests\Data\ExampleDataModel;
use Flat3\OData\Tests\Data\FlightDataModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class ActionTest extends TestCase
{
    use ExampleDataModel;
    use FlightDataModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightDataModel();
        $this->withExampleDataModel();
    }

    public function test_metadata()
    {
        $this->assertXmlResponse(
            Request::factory()
                ->xml()
                ->path('/$metadata')
        );
    }

    public function test_callback()
    {
        $this->assertJsonResponse(
            Request::factory()
                ->path('/example()')
        );
    }

    public function test_callback_entity()
    {
        /** @var DataModel $model */
        $model = app()->make(DataModel::class);

        $callback = $model->getResources()->get('example');
        $callback->setCallback(function () use ($model) {
            $entity = new Entity($model->getResources()->get('airports'));

            /** @var EntityType $airport */
            $airport = $model->getEntityTypes()->get('airport');

            $entity->addPrimitive('abc', $airport->getProperty('code'));

            return $entity;
        });

        $model->addResource($callback);

        $this->assertJsonResponse(
            Request::factory()
                ->path('/example()')
        );
    }

    public function test_callback_entity_set()
    {
        $this->markTestIncomplete();

        /** @var DataModel $model */
        $model = app()->make(DataModel::class);

        $callback = new Function_('example', 'airport');
        $callback->setCallback(function () use ($model) {
            $store = $model->getResources()->get('airports');

            $entity = new Entity($store);

            /** @var EntityType $airport */
            $airport = $model->getEntityTypes()->get('airport');

            $entity->addPrimitive('abc', $airport->getProperty('code'));

            $entityset = new EntitySet\Dynamic($store);
            $entityset->addResult($entity);

            return $entityset;
        });

        $model->addResource($callback);

        $this->assertJsonResponse(
            Request::factory()
                ->path('/example()')
        );
    }
}