<?php

namespace Flat3\OData\Tests\Unit\Operation;

use Flat3\OData\EntitySet;
use Flat3\OData\Exception\Protocol\InternalServerErrorException;
use Flat3\OData\Model;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;

class FunctionTest extends TestCase
{
    public function test_callback()
    {
        $this->withFlightModel();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/exf1()')
        );
    }

    public function test_callback_entity()
    {
        $this->withFlightModel();

        $this->assertJsonResponse(
            Request::factory()
                ->path("/exf3(code='xyz')")
        );
    }

    public function test_callback_entity_set()
    {
        $this->withTextModel();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/textf1()')
        );
    }

    public function test_with_arguments()
    {
        $this->withFlightModel();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/add(a=3,b=4)')
        );
    }

    public function test_with_argument_order()
    {
        $this->withFlightModel();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/div(a=3,b=4)')
        );

        $this->assertJsonResponse(
            Request::factory()
                ->path('/div(b=3,a=4)')
        );
    }

    public function test_with_indirect_arguments()
    {
        $this->withFlightModel();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/add(a=@c,b=@d)')
                ->query('@c', 1)
                ->query('@d', 2)
        );
    }

    public function test_with_single_indirect_argument()
    {
        $this->withFlightModel();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/add(a=@c,b=@c)')
                ->query('@c', 1)
        );
    }

    public function test_with_missing_indirect_arguments()
    {
        $this->withFlightModel();

        $this->assertBadRequest(
            Request::factory()
                ->path('/add(a=@c,b=@e)')
                ->query('@c', 1)
                ->query('@d', 2)
        );
    }

    public function test_callback_modified_flight_entity_set()
    {
        $this->withFlightModel();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/ffn1()')
        );
    }

    public function test_callback_bound_entity_set()
    {
        $this->withFlightModel();

        Model::fn('ffb1')
            ->setCallback(function (EntitySet $flights): EntitySet {
                return $flights;
            })
            ->setBindingParameter('flights')
            ->setType(Model::getType('flight'));

        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights/ffb1()')
        );
    }

    public function test_void_callback()
    {
        $this->withTextModel();

        $this->expectException(InternalServerErrorException::class);
        Model::fn('textv1')
            ->setCallback(function (): void {
            });
    }

    public function test_default_null_callback()
    {
        $this->withTextModel();

        $this->expectException(InternalServerErrorException::class);
        Model::fn('textv1')
            ->setCallback(function () {
            });
    }
}