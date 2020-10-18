<?php

namespace Flat3\Lodata\Tests\Unit\Operation;

use Flat3\Lodata\Entity;
use Flat3\Lodata\Interfaces\ActionInterface;
use Flat3\Lodata\Model;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type\Int32;
use Flat3\Lodata\Type\String_;

class ActionTest extends TestCase
{
    public function test_get_not_allowed()
    {
        Model::add(new class('exa1') extends Operation implements ActionInterface {
            public function invoke(): String_
            {
                return String_::factory('hello');
            }
        });

        $this->assertMethodNotAllowed(
            Request::factory()
                ->path('/exa1()')
        );
    }

    public function test_callback()
    {
        Model::add(new class('exa1') extends Operation implements ActionInterface {
            public function invoke(): String_
            {
                return String_::factory('hello');
            }
        });

        $this->assertJsonResponse(
            Request::factory()
                ->post()
                ->path('/exa1()')
        );
    }

    public function test_service_document()
    {
        Model::add(new class('exa1') extends Operation implements ActionInterface {
            public function invoke(): String_
            {
                return String_::factory('hello');
            }
        });

        $this->assertJsonResponse(
            Request::factory()
        );
    }

    public function test_callback_entity()
    {
        $this->assertNotFound(
            Request::factory()
                ->path('/exa2()')
        );
    }

    public function test_no_composition()
    {
        Model::add(new class('textv1') extends Operation implements ActionInterface {
            public function invoke(): Int32
            {
                return new Int32(3);
            }
        });

        $this->assertBadRequest(
            Request::factory()
                ->post()
                ->path('/textv1()/$value')
        );
    }

    public function test_void_callback()
    {
        Model::add(new class('textv1') extends Operation implements ActionInterface {
            public function invoke(): void
            {

            }
        });

        $this->assertNoContent(
            Request::factory()
                ->post()
                ->path('/textv1()')
        );
    }

    public function test_default_null_callback()
    {
        Model::add(new class('textv1') extends Operation implements ActionInterface {
            public function invoke()
            {

            }
        });


        $this->assertNoContent(
            Request::factory()
                ->post()
                ->path('/textv1()')
        );
    }

    public function test_explicit_null_callback()
    {
        Model::add(new class('textv1') extends Operation implements ActionInterface {
            public function invoke()
            {
                return null;
            }
        });

        $this->assertNoContent(
            Request::factory()
                ->post()
                ->path('/textv1()')
        );
    }

    public function test_bound()
    {
        $this->withFlightModel();

        Model::add((new class('aa1') extends Operation implements ActionInterface {
            public function invoke(Entity $airport): Entity
            {
                return $airport;
            }
        })->setBindingParameterName('airport')->setType(Model::getType('airport')));

        $this->assertJsonResponse(
            Request::factory()
                ->post()
                ->path('/airports(1)/aa1')
        );
    }

    public function test_parameters()
    {
        Model::add(new class('aa1') extends Operation implements ActionInterface {
            public function invoke(Int32 $a, Int32 $b): Int32
            {
                return new Int32($a->get() + $b->get());
            }
        });

        $this->assertJsonResponse(
            Request::factory()
                ->post()
                ->body([
                    'a' => 3,
                    'b' => 4,
                ])
                ->path('/aa1')
        );
    }

    public function test_prefers_no_results()
    {
        Model::add(new class('aa1') extends Operation implements ActionInterface {
            public function invoke(): Int32
            {
                return new Int32(99);
            }
        });

        $this->assertNoContent(
            Request::factory()
                ->post()
                ->body([
                    'a' => 3,
                    'b' => 4,
                ])
                ->path('/aa1')
                ->header('Prefer', 'return=minimal')
        );
    }

    public function test_parameters_invalid_body_string()
    {
        Model::add(new class('aa1') extends Operation implements ActionInterface {
            public function invoke(Int32 $a, Int32 $b): Int32
            {
                return new Int32($a->get() + $b->get());
            }
        });

        $this->assertNotAcceptable(
            Request::factory()
                ->post()
                ->body('[d')
                ->path('/aa1')
        );
    }

    public function test_parameters_invalid_body_array()
    {
        $this->withFlightModel();

        Model::add(new class('aa1') extends Operation implements ActionInterface {
            public function invoke(Int32 $a, Int32 $b): Int32
            {
                return new Int32($a->get() + $b->get());
            }
        });

        $this->assertBadRequest(
            Request::factory()
                ->post()
                ->header('content-type', 'application/json')
                ->body('[d')
                ->path('/aa1')
        );
    }
}