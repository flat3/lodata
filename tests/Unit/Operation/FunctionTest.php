<?php

namespace Flat3\Lodata\Tests\Unit\Operation;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Model;
use Flat3\Lodata\Tests\Data\Airport;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type\Int32;
use Flat3\Lodata\Type\String_;

class FunctionTest extends TestCase
{
    public function test_callback()
    {
        Model::fn('exf1')
            ->setCallback(function (): String_ {
                return String_::factory('hello');
            });

        $this->assertJsonResponse(
            Request::factory()
                ->path('/exf1()')
        );
    }

    public function test_callback_no_parentheses()
    {
        Model::fn('exf1')
            ->setCallback(function (): String_ {
                return String_::factory('hello');
            });

        $this->assertJsonResponse(
            Request::factory()
                ->path('/exf1')
        );
    }

    public function test_service_document()
    {
        Model::fn('exf1')
            ->setCallback(function (): String_ {
                return String_::factory('hello');
            });

        $this->assertJsonResponse(
            Request::factory()
        );
    }

    public function test_callback_entity()
    {
        $this->withFlightModel();

        Model::fn('exf3')
            ->setCallback(function (String_ $code): Entity {
                /** @var Model $model */
                $model = app()->get(Model::class);
                $airport = new Airport();
                $airport->setType($model->getEntityTypes()->get('airport'));
                $airport['code'] = $code->get();
                return $airport;
            })
            ->setType(Model::getType('airport'));

        $this->assertJsonResponse(
            Request::factory()
                ->path("/exf3(code='xyz')")
        );
    }

    public function test_callback_entity_set()
    {
        $this->withTextModel();

        Model::fn('textf1')
            ->setCallback(function (EntitySet $texts): EntitySet {
                return $texts;
            })
            ->setType(Model::getType('text'));

        $this->assertJsonResponse(
            Request::factory()
                ->path('/textf1()')
        );
    }

    public function test_with_arguments()
    {
        $this->withMathFunctions();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/add(a=3,b=4)')
        );
    }

    public function test_with_argument_order()
    {
        $this->withMathFunctions();

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
        $this->withMathFunctions();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/add(a=@c,b=@d)')
                ->query('@c', 1)
                ->query('@d', 2)
        );
    }

    public function test_with_single_indirect_argument()
    {
        $this->withMathFunctions();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/add(a=@c,b=@c)')
                ->query('@c', 1)
        );
    }

    public function test_with_missing_indirect_arguments()
    {
        $this->withMathFunctions();

        $this->assertBadRequest(
            Request::factory()
                ->path('/add(a=@c,b=@e)')
                ->query('@c', 1)
                ->query('@d', 2)
        );
    }

    public function test_with_implicit_parameter_aliases()
    {
        $this->withMathFunctions();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/add')
                ->query('a', 1)
                ->query('b', 2)
        );
    }

    public function test_with_implicit_parameter_alias_matching_system_query_option()
    {
        Model::fn('add')
            ->setCallback(function (Int32 $apply, Int32 $compute): Int32 {
                return Int32::factory($apply->get() + $compute->get());
            });

        $this->assertJsonResponse(
            Request::factory()
                ->path('/add')
                ->query('@apply', 1)
                ->query('@compute', 2)
        );
    }

    public function test_function_composition()
    {
        Model::fn('identity')
            ->setCallback(function (Int32 $i): Int32 {
                return Int32::factory($i->get());
            });

        Model::fn('increment')
            ->setCallback(function (Int32 $i): Int32 {
                return Int32::factory($i->get() + 1);
            })->setBindingParameterName('i');

        $this->assertJsonResponse(
            Request::factory()
                ->path('/identity(i=1)/increment/increment')
        );
    }

    public function test_callback_modified_flight_entity_set()
    {
        $this->withFlightModel();

        Model::fn('ffn1')
            ->setCallback(function (Transaction $transaction, EntitySet $flights): EntitySet {
                $transaction->getSelect()->setValue('origin');
                return $flights;
            })
            ->setType(Model::getType('flight'));

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
            ->setBindingParameterName('flights')
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