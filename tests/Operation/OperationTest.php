<?php

namespace Flat3\Lodata\Tests\Operation;

use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\Laravel\Services\Service;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type\Decimal;
use Flat3\Lodata\Type\Duration;
use Flat3\Lodata\Type\Int32;
use Flat3\Lodata\Type\String_;
use Illuminate\Support\Facades\Gate;

class OperationTest extends TestCase
{
    use WithNumericCollectionDriver;

    public function test_callback()
    {
        $op = new Operation\Function_('exf1');
        $op->setCallable(function (): String_ {
            return new String_('hello');
        });
        Lodata::add($op);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/exf1()')
        );
    }

    public function test_object_callback()
    {
        $op = new Operation\Function_('exf1');
        $op->setCallable([Service::class, 'hello']);
        Lodata::add($op);

        $this->assertJsonResponseSnapshot(
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

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/exf1()')
        );
    }

    public function test_namespaced()
    {
        $op = new Operation\Function_('com.example.odata1.exf1');
        $op->setCallable(function (): String_ {
            return new String_('hello');
        });
        Lodata::add($op);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/com.example.odata1.exf1()')
        );

        $this->assertNotFound(
            (new Request)
                ->path('/com.example.odata.exf1()')
        );

        $this->assertNotFound(
            (new Request)
                ->path('/exf1()')
        );

        $this->assertNotFound(
            (new Request)
                ->path('/com.example.odata2.exf1()')
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

        $this->assertMetadataSnapshot();
    }

    public function test_binding_did_not_exist()
    {
        $number = new Operation\Function_('number');
        $number->setCallable(function (): int {
            return 42;
        });

        Lodata::add($number);

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

        $this->assertMetadataSnapshot();
    }

    public function test_parameter_order_bound()
    {
        $op = new Operation\Function_('f1');
        $op->setCallable(function (Int32 $a, Decimal $b): Int32 {
            return new Int32(0);
        })->setBindingParameterName('b');
        Lodata::add($op);

        $this->assertMetadataSnapshot();
    }

    public function test_parameter_bound_passthru()
    {
        $op = new Operation\Function_('f1');
        $op->setCallable(function (?Decimal $b, EntitySet $passengers): EntitySet {
            return $passengers;
        })
            ->setBindingParameterName('passengers')
            ->setReturnType(Lodata::getEntityType('passenger'));
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/passengers/f1()')
        );
    }

    public function test_parameter_bound_modify()
    {
        $op = new Operation\Function_('f1');
        $op->setCallable(function (?Decimal $b, EntitySet $passengers): Duration {
            $entity = $passengers->query()->current();
            $duration = $entity['in_role'];
            return $duration->getValue();
        });
        $op->setBindingParameterName('passengers');

        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/passengers/f1()')
        );
    }

    public function test_parameter_bound_missing_binding()
    {
        $op = new Operation\Function_('f1');
        $op->setCallable(function (?Decimal $b, EntitySet $passengers) {
            return $passengers;
        });
        $op->setBindingParameterName('passengers');
        $op->setReturnType(Lodata::getEntityType('passenger'));
        Lodata::add($op);

        $this->assertBadRequest(
            (new Request)
                ->path('/f1()')
        );
    }

    public function test_parameter_bound_binding_wrong_type()
    {
        $op = new Operation\Function_('f1');
        $op->setCallable(function (?Decimal $b, EntitySet $passengers): EntitySet {
            return $passengers;
        });
        $op->setBindingParameterName('passengers');
        $op->setReturnType(Lodata::getEntityType('passenger'));
        Lodata::add($op);

        $this->assertBadRequest(
            (new Request)
                ->path('/passengers(1)/f1()')
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

        $this->assertJsonResponseSnapshot(
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

    public function test_gate_readonly_override()
    {
        config([
            'lodata.readonly' => true,
            'lodata.authorization' => true,
        ]);

        $op = new Operation\Function_('exf1');

        Gate::shouldReceive('allows')->andReturnUsing(function (\Flat3\Lodata\Helper\Gate $gate) use ($op) {
            return $op->getName() === $gate->getResource()->getName();
        });

        $op->setCallable(function (string $arg): string {
            return $arg;
        });

        Lodata::add($op);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/exf1(arg='hello')")
        );
    }
}
