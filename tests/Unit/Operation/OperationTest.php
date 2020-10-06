<?php

namespace Flat3\OData\Tests\Unit\Operation;

use Flat3\OData\EntitySet;
use Flat3\OData\Exception\Protocol\InternalServerErrorException;
use Flat3\OData\Model;
use Flat3\OData\Tests\Data\FlightModel;
use Flat3\OData\Tests\Request;
use Flat3\OData\Tests\TestCase;
use Flat3\OData\Type\Decimal;
use Flat3\OData\Type\Int32;
use Flat3\OData\Type\String_;

class OperationTest extends TestCase
{
    use FlightModel;

    public function test_missing_callback()
    {
        $this->expectException(InternalServerErrorException::class);
        Model::fn('f1')
            ->setBindingParameter('texts');
    }

    public function test_binding_did_not_exist()
    {
        $this->expectException(InternalServerErrorException::class);
        Model::fn('f1')
            ->setCallback(function (Int32 $taxts) {
            })
            ->setBindingParameter('texts');
    }

    public function test_parameter_order_unbound()
    {
        Model::fn('f1')
            ->setCallback(function (Int32 $a, Decimal $b): Int32 {
                return new Int32(0);
            });

        $this->assertXmlResponse(
            Request::factory()
                ->xml()
                ->path('/$metadata')
        );
    }

    public function test_parameter_order_bound()
    {
        Model::fn('f1')
            ->setCallback(function (Int32 $a, Decimal $b): Int32 {
                return new Int32(0);
            })
            ->setBindingParameter('b');

        $this->assertXmlResponse(
            Request::factory()
                ->xml()
                ->path('/$metadata')
        );
    }

    public function test_parameter_bound_passthru()
    {
        $this->withFlightModel();

        Model::fn('f1')
            ->setCallback(function (?Decimal $b, EntitySet $flights): EntitySet {
                return $flights;
            })
            ->setBindingParameter('flights')
            ->setType(Model::getType('flight'));

        $this->assertXmlResponse(
            Request::factory()
                ->xml()
                ->path('/$metadata')
        );

        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights/f1()')
        );
    }

    public function test_parameter_bound_modify()
    {
        $this->withFlightModel();

        Model::fn('f1')
            ->setCallback(function (?Decimal $b, EntitySet $flights): Decimal {
                return new Decimal($flights->count());
            })
            ->setBindingParameter('flights');

        $this->assertXmlResponse(
            Request::factory()
                ->xml()
                ->path('/$metadata')
        );

        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights/f1()')
        );
    }

    public function test_parameter_bound_missing_binding()
    {
        $this->withFlightModel();

        Model::fn('f1')
            ->setCallback(function (?Decimal $b, EntitySet $flights): EntitySet {
                return $flights;
            })
            ->setBindingParameter('flights')
            ->setType(Model::getType('flight'));

        $this->assertBadRequest(
            Request::factory()
                ->path('/f1()')
        );
    }

    public function test_parameter_bound_binding_wrong_type()
    {
        $this->withFlightModel();

        Model::fn('f1')
            ->setCallback(function (?Decimal $b, EntitySet $flights): EntitySet {
                return $flights;
            })
            ->setBindingParameter('flights')
            ->setType(Model::getType('flight'));

        $this->assertBadRequest(
            Request::factory()
                ->path('/flights(1)/f1()')
        );
    }

    public function test_function_pipe()
    {
        Model::fn('hello')
            ->setCallback(function (): String_ {
                return new String_('hello');
            });

        Model::fn('world')
            ->setCallback(function (String_ $second): String_ {
                return new String_($second->get().' world');
            })
            ->setBindingParameter('second');

        $this->assertJsonResponse(
            Request::factory()
                ->path('hello()/world()')
        );
    }
}
