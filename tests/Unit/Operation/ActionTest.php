<?php

namespace Flat3\Lodata\Tests\Unit\Operation;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\Operation\ActionInterface;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type\Int32;
use Flat3\Lodata\Type\String_;

class ActionTest extends TestCase
{
    public function test_get_not_allowed()
    {
        Lodata::add(new class('exa1') extends Operation implements ActionInterface {
            public function invoke(): String_
            {
                return new String_('hello');
            }
        });

        $this->assertMethodNotAllowed(
            (new Request)
                ->path('/exa1()')
        );
    }

    public function test_callback()
    {
        Lodata::add(new class('exa1') extends Operation implements ActionInterface {
            public function invoke(): String_
            {
                return new String_('hello');
            }
        });

        $this->assertJsonResponse(
            (new Request)
                ->post()
                ->path('/exa1()')
        );
    }

    public function test_service_document()
    {
        Lodata::add(new class('exa1') extends Operation implements ActionInterface {
            public function invoke(): String_
            {
                return new String_('hello');
            }
        });

        $this->assertJsonResponse(
            (new Request)
        );
    }

    public function test_callback_entity()
    {
        $this->assertNotFound(
            (new Request)
                ->path('/exa2()')
        );
    }

    public function test_no_composition()
    {
        Lodata::add(new class('textv1') extends Operation implements ActionInterface {
            public function invoke(): Int32
            {
                return new Int32(3);
            }
        });

        $this->assertBadRequest(
            (new Request)
                ->post()
                ->path('/textv1()/$value')
        );
    }

    public function test_void_callback()
    {
        Lodata::add(new class('textv1') extends Operation implements ActionInterface {
            public function invoke(): void
            {

            }
        });

        $this->assertNoContent(
            (new Request)
                ->post()
                ->path('/textv1()')
        );
    }

    public function test_default_null_callback()
    {
        Lodata::add(new class('textv1') extends Operation implements ActionInterface {
            public function invoke()
            {

            }
        });


        $this->assertNoContent(
            (new Request)
                ->post()
                ->path('/textv1()')
        );
    }

    public function test_explicit_null_callback()
    {
        Lodata::add(new class('textv1') extends Operation implements ActionInterface {
            public function invoke()
            {
                return null;
            }
        });

        $this->assertNoContent(
            (new Request)
                ->post()
                ->path('/textv1()')
        );
    }

    public function test_bound()
    {
        $this->withFlightModel();

        Lodata::add((new class('aa1') extends Operation implements ActionInterface {
            public function invoke(Entity $airport): Entity
            {
                return $airport;
            }
        })->setBindingParameterName('airport')->setReturnType(Lodata::getEntityType('airport')));

        $this->assertJsonResponse(
            (new Request)
                ->post()
                ->path('/airports(1)/aa1')
        );
    }

    public function test_create()
    {
        $this->withFlightModel();

        Lodata::add((new class('aa1') extends Operation implements ActionInterface {
            public function invoke(EntitySet $airports, Transaction $transaction): Entity
            {
                $transaction->getResponse()->setStatusCode(Response::HTTP_CREATED);

                $entity = $airports->newEntity();
                $entity->setEntityId(4);

                return $entity;
            }
        })->setReturnType(Lodata::getEntityType('airport')));

        $this->assertJsonResponse(
            (new Request)
                ->post()
                ->path('/airports/aa1'),
            Response::HTTP_CREATED
        );
    }

    public function test_parameters()
    {
        Lodata::add(new class('aa1') extends Operation implements ActionInterface {
            public function invoke(Int32 $a, Int32 $b): Int32
            {
                return new Int32($a->get() + $b->get());
            }
        });

        $this->assertJsonResponse(
            (new Request)
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
        Lodata::add(new class('aa1') extends Operation implements ActionInterface {
            public function invoke(): Int32
            {
                return new Int32(99);
            }
        });

        $this->assertNoContent(
            (new Request)
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
        Lodata::add(new class('aa1') extends Operation implements ActionInterface {
            public function invoke(Int32 $a, Int32 $b): Int32
            {
                return new Int32($a->get() + $b->get());
            }
        });

        $this->assertNotAcceptable(
            (new Request)
                ->post()
                ->body('[d')
                ->path('/aa1')
        );
    }

    public function test_parameters_invalid_body_array()
    {
        $this->withFlightModel();

        Lodata::add(new class('aa1') extends Operation implements ActionInterface {
            public function invoke(Int32 $a, Int32 $b): Int32
            {
                return new Int32($a->get() + $b->get());
            }
        });

        $this->assertBadRequest(
            (new Request)
                ->post()
                ->header('content-type', 'application/json')
                ->body('[d')
                ->path('/aa1')
        );
    }

    public function test_null_typed_callback()
    {
        Lodata::add(new class('booleanv1') extends Operation implements ActionInterface {
            public function invoke(): ?bool
            {
                return null;
            }
        });

        $this->assertMetadataDocuments();

        $this->assertNoContent(
            (new Request)
                ->post()
                ->path('/booleanv1()')
        );
    }
}