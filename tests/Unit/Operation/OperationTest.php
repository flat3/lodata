<?php

namespace Flat3\Lodata\Tests\Unit\Operation;

use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Protocol\ProtocolException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\FunctionInterface;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type\Decimal;
use Flat3\Lodata\Type\Int32;
use Flat3\Lodata\Type\String_;

class OperationTest extends TestCase
{
    public function test_missing_invoke()
    {
        try {
            Lodata::add((new class('f1') extends Operation implements FunctionInterface {
            })->setBindingParameterName('texts'));
        } catch (ProtocolException $e) {
            $this->assertProtocolExceptionSnapshot($e);
        }
    }

    public function test_binding_did_not_exist()
    {
        try {
            Lodata::add((new class('f1') extends Operation implements FunctionInterface {
                function invoke(Int32 $taxts)
                {
                }
            })->setBindingParameterName('texts'));
        } catch (ProtocolException $e) {
            $this->assertProtocolExceptionSnapshot($e);
        }
    }

    public function test_parameter_order_unbound()
    {
        Lodata::add((new class('f1') extends Operation implements FunctionInterface {
            function invoke(Int32 $a, Decimal $b): Int32
            {
                return new Int32(0);
            }
        }));

        $this->assertXmlResponse(
            Request::factory()
                ->xml()
                ->path('/$metadata')
        );
    }

    public function test_parameter_order_bound()
    {
        Lodata::add((new class('f1') extends Operation implements FunctionInterface {
            function invoke(Int32 $a, Decimal $b): Int32
            {
                return new Int32(0);
            }
        })->setBindingParameterName('b'));

        $this->assertXmlResponse(
            Request::factory()
                ->xml()
                ->path('/$metadata')
        );
    }

    public function test_parameter_bound_passthru()
    {
        $this->withFlightModel();

        Lodata::add((new class('f1') extends Operation implements FunctionInterface {
            public function invoke(?Decimal $b, EntitySet $flights): EntitySet
            {
                return $flights;
            }
        })->setBindingParameterName('flights')->setReturnType(Lodata::getEntityType('flight')));

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

        Lodata::add((new class('f1') extends Operation implements FunctionInterface {
            public function invoke(?Decimal $b, EntitySet $flights): Decimal
            {
                return new Decimal($flights->count());
            }
        })->setBindingParameterName('flights'));

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

        Lodata::add((new class('f1') extends Operation implements FunctionInterface {
            public function invoke(?Decimal $b, EntitySet $flights)
            {
                return $flights;
            }
        })->setBindingParameterName('flights')->setReturnType(Lodata::getEntityType('flight')));

        $this->assertBadRequest(
            Request::factory()
                ->path('/f1()')
        );
    }

    public function test_parameter_bound_binding_wrong_type()
    {
        $this->withFlightModel();

        Lodata::add((new class('f1') extends Operation implements FunctionInterface {
            public function invoke(?Decimal $b, EntitySet $flights): EntitySet
            {
                return $flights;
            }
        })->setBindingParameterName('flights')->setReturnType(Lodata::getEntityType('flight')));

        $this->assertBadRequest(
            Request::factory()
                ->path('/flights(1)/f1()')
        );
    }

    public function test_function_pipe()
    {
        Lodata::add(new class('hello') extends Operation implements FunctionInterface {
            public function invoke(): String_
            {
                return new String_('hello');
            }
        });

        Lodata::add((new class('world') extends Operation implements FunctionInterface {
            public function invoke(String_ $second): String_
            {
                return new String_($second->get().' world');
            }
        })->setBindingParameterName('second'));

        $this->assertJsonResponse(
            Request::factory()
                ->path('hello()/world()')
        );
    }
}
