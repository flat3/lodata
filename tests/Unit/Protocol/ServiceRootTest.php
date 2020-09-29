<?php

namespace Flat3\OData\Tests\Unit\Protocol;

use Flat3\OData\DataModel;
use Flat3\OData\Operation\Function_;
use Flat3\OData\Tests\Data\FlightDataModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;
use Flat3\OData\Type\String_;

class ServiceRootTest extends TestCase
{
    use FlightDataModel;

    public function test_has_empty_service_document_at_service_root()
    {
        $this->assertJsonMetadataResponse(
            Request::factory()
        );
    }

    public function test_has_flight_service_document_at_service_root()
    {
        $this->withFlightDataModel();

        $this->assertJsonMetadataResponse(
            Request::factory()
        );
    }

    public function test_has_operation_service_document_at_service_root()
    {
        /** @var DataModel $model */
        $model = app()->make(DataModel::class);

        $callback = new Function_('example', String_::class);

        $model->addResource($callback);

        $this->assertJsonMetadataResponse(
            Request::factory()
        );
    }
}
