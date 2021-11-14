<?php

namespace Flat3\Lodata\Tests\Unit\Operation;

use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Tests\Operations\Service;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type\Decimal;
use Flat3\Lodata\Type\Duration;
use Flat3\Lodata\Type\Int32;
use Flat3\Lodata\Type\String_;

class OperationTest extends TestCase
{
    public function test_callback()
    {
        $op = new Operation\Function_('exf1');
        $op->setCallable(function (): String_ {
            return new String_('hello');
        });
        Lodata::add($op);

        $this->assertJsonResponse(
            (new Request)
                ->path('/exf1()')
        );
    }

    public function test_object_callback()
    {
        $op = new Operation\Function_('exf1');
        $op->setCallable([Service::class, 'hello']);
        Lodata::add($op);

        $this->assertJsonResponse(
            (new Request)
                ->path('/exf1()')
        );
    }

    public function test_instance_callback()
    {
        $op = new Operation\Function_('exf1');
        $c = new Service();
        $op->setCallable([$c, 'hello']);
        Lodata::add($op);

        $this->assertJsonResponse(
            (new Request)
                ->path('/exf1()')
        );
    }

    public function test_missing_invoke()
    {
        $op = new Operation\Function_('f1');
        Lodata::add($op);

        $this->assertInternalServerError(
            (new Request)
                ->path('/f1()')
        );
    }

    public function test_void_uses_string()
    {
        $op = new Operation\Function_('f1');
        $op->setCallable(function () {
            return 'hello';
        });
        Lodata::add($op);

        $this->assertMetadataDocuments();
    }

    public function test_binding_did_not_exist()
    {
        $this->withNumberFunction();
        $op = new Operation\Function_('f1');
        $op->setCallable(function (?Int32 $taxts) {
            return 42;
        });
        $op->setBindingParameterName('texts');
        Lodata::add($op);

        $this->assertInternalServerError(
            (new Request)
                ->path('/number()/f1()')
        );
    }

    public function test_parameter_order_unbound()
    {
        $op = new Operation\Function_('f1');
        $op->setCallable(function (Int32 $a, Decimal $b): Int32 {
            return new Int32(0);
        });
        Lodata::add($op);

        $this->assertMetadataDocuments();
    }

    public function test_parameter_order_bound()
    {
        $op = new Operation\Function_('f1');
        $op->setCallable(function (Int32 $a, Decimal $b): Int32 {
            return new Int32(0);
        })->setBindingParameterName('b');
        Lodata::add($op);

        $this->assertMetadataDocuments();
    }

    public function test_parameter_bound_passthru()
    {
        $this->withFlightModel();

        $op = new Operation\Function_('f1');
        $op->setCallable(function (?Decimal $b, EntitySet $flights): EntitySet {
            return $flights;
        })
            ->setBindingParameterName('flights')
            ->setReturnType(Lodata::getEntityType('flight'));
        Lodata::add($op);

        $this->assertMetadataDocuments();

        $this->assertJsonResponse(
            (new Request)
                ->path('/flights/f1()')
        );
    }

    public function test_parameter_bound_modify()
    {
        $this->withFlightModel();

        $op = new Operation\Function_('f1');
        $op->setCallable(function (?Decimal $b, EntitySet $flights): Duration {
            $entity = $flights->query()->current();
            $duration = $entity['duration'];
            return $duration->getValue();
        });
        $op->setBindingParameterName('flights');

        Lodata::add($op);

        $this->assertMetadataDocuments();

        $this->assertJsonResponse(
            (new Request)
                ->path('/flights/f1()')
        );
    }

    public function test_parameter_bound_missing_binding()
    {
        $this->withFlightModel();

        $op = new Operation\Function_('f1');
        $op->setCallable(function (?Decimal $b, EntitySet $flights) {
            return $flights;
        });
        $op->setBindingParameterName('flights');
        $op->setReturnType(Lodata::getEntityType('flight'));
        Lodata::add($op);

        $this->assertBadRequest(
            (new Request)
                ->path('/f1()')
        );
    }

    public function test_parameter_bound_binding_wrong_type()
    {
        $this->withFlightModel();

        $op = new Operation\Function_('f1');
        $op->setCallable(function (?Decimal $b, EntitySet $flights): EntitySet {
            return $flights;
        });
        $op->setBindingParameterName('flights');
        $op->setReturnType(Lodata::getEntityType('flight'));
        Lodata::add($op);

        $this->assertBadRequest(
            (new Request)
                ->path('/flights(1)/f1()')
        );
    }

    public function test_function_pipe()
    {
        $hello = new Operation\Function_('hello');
        $hello->setCallable(function (): String_ {
            return new String_('hello');
        });
        Lodata::add($hello);

        $world = new Operation\Function_('world');
        $world->setCallable(function (String_ $second): String_ {
            return new String_($second->get().' world');
        });
        $world->setBindingParameterName('second');
        Lodata::add($world);

        $this->assertJsonResponse(
            (new Request)
                ->path('hello()/world()')
        );
    }

    public function test_malformed_args()
    {
        $op = new Operation\Function_('exf1');
        $op->setCallable(function (string $a): string {
            return $a;
        });
        Lodata::add($op);

        $this->assertBadRequest(
            (new Request)
                ->path('/exf1(a=')
        );
    }
}
