<?php

namespace Flat3\OData\Tests\Unit\Operation;

use Flat3\OData\DataModel;
use Flat3\OData\Entity;
use Flat3\OData\EntityType;
use Flat3\OData\Operation\Function_;
use Flat3\OData\Tests\Data\FlightDataModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;
use Flat3\OData\Type\String_;

class FunctionTest extends TestCase
{
    use FlightDataModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->withFlightDataModel();
    }

    public function test_callback()
    {
        /** @var DataModel $model */
        $model = app()->make(DataModel::class);

        $callback = new Function_('example', String_::class);
        $callback->setCallback(function () {
            return String_::factory('hello');
        });

        $model->resource($callback);

        $this->assertJsonResponse(
            Request::factory()
                ->path('/example()')
        );
    }

    public function test_callback_entity()
    {
        /** @var DataModel $model */
        $model = app()->make(DataModel::class);

        $callback = new Function_('example', 'airport');
        $callback->setCallback(function () use ($model) {
            $entity = new Entity($model->getResources()->get('airports'));

            /** @var EntityType $airport */
            $airport = $model->getEntityTypes()->get('airport');

            $entity->addPrimitive('abc', $airport->getProperty('code'));

            return $entity;
        });

        $model->resource($callback);

        $this->assertJsonResponse(
            Request::factory()
                ->path('/example()')
        );
    }
}