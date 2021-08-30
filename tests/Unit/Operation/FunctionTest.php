<?php

namespace Flat3\Lodata\Tests\Unit\Operation;

use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Drivers\StaticEntitySet;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Interfaces\Operation\FunctionInterface;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Tests\Data\Airport;
use Flat3\Lodata\Tests\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type\Int32;
use Flat3\Lodata\Type\String_;

class FunctionTest extends TestCase
{
    public function test_callback()
    {
        Lodata::add(new class('exf1') extends Operation implements FunctionInterface {
            function invoke(): String_
            {
                return String_::factory('hello');
            }
        });

        $this->assertJsonResponse(
            Request::factory()
                ->path('/exf1()')
        );
    }

    public function test_callback_no_parentheses()
    {
        Lodata::add(new class('exf1') extends Operation implements FunctionInterface {
            function invoke(): String_
            {
                return String_::factory('hello');
            }
        });

        $this->assertJsonResponse(
            Request::factory()
                ->path('/exf1')
        );
    }

    public function test_service_document()
    {
        Lodata::add(new class('exf1') extends Operation implements FunctionInterface {
            function invoke(): String_
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
        $this->withFlightModel();

        Lodata::add((new class('exf3') extends Operation implements FunctionInterface {
            function invoke(String_ $code): Entity
            {
                $airport = new Airport();
                $airport->setType(Lodata::getEntityType('airport'));
                $airport['code'] = $code->get();
                return $airport;
            }
        })->setReturnType(Lodata::getEntityType('airport')));

        $this->assertJsonResponse(
            Request::factory()
                ->path("/exf3(code='xyz')")
        );
    }

    public function test_callback_entity_set()
    {
        $this->withTextModel();

        Lodata::add((new class('textf1') extends Operation implements FunctionInterface {
            public function invoke(EntitySet $texts): EntitySet
            {
                return $texts;
            }
        })->setReturnType(Lodata::getEntityType('text')));

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
        Lodata::add(new class('add') extends Operation implements FunctionInterface {
            public function invoke(Int32 $apply, Int32 $compute): Int32
            {
                return Int32::factory($apply->get() + $compute->get());
            }
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
        Lodata::add(new class('identity') extends Operation implements FunctionInterface {
            public function invoke(Int32 $i): Int32
            {
                return Int32::factory($i->get());
            }
        });

        Lodata::add((new class('increment') extends Operation implements FunctionInterface {
            public function invoke(Int32 $i): Int32
            {
                return Int32::factory($i->get() + 1);
            }
        })->setBindingParameterName('i'));

        $this->assertJsonResponse(
            Request::factory()
                ->path('/identity(i=1)/increment/increment')
        );
    }

    public function test_callback_modified_flight_entity_set()
    {
        $this->withFlightModel();

        Lodata::add((new class('ffn1') extends Operation implements FunctionInterface {
            public function invoke(Transaction $transaction, EntitySet $flights): EntitySet
            {
                $transaction->getSelect()->setValue('origin');
                return $flights;
            }
        })->setReturnType(Lodata::getEntityType('flight')));

        $this->assertJsonResponse(
            Request::factory()
                ->path('/ffn1()')
        );
    }

    public function test_callback_bound_entity_set()
    {
        $this->withFlightModel();

        Lodata::add((new class('ffb1') extends Operation implements FunctionInterface {
            public function invoke(EntitySet $flights): EntitySet
            {
                return $flights;
            }
        })->setBindingParameterName('flights')->setReturnType(Lodata::getEntityType('flight')));

        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights/ffb1()')
        );
    }

    public function test_callback_bound_entity_set_with_filter()
    {
        $this->withFlightModel();

        Lodata::add((new class('sorter') extends Operation implements FunctionInterface {
            public function invoke(String_ $field, EntitySet $airports): EntitySet
            {
                $result = new StaticEntitySet($airports->getType());
                $result->setIdentifier($airports->getIdentifier());

                foreach ($airports->query() as $airport) {
                    $result[] = $airport;
                }

                $result->sort(function (Entity $a1, Entity $a2) use ($field) {
                    return $a1[$field->get()]->getPrimitiveValue() <=> $a2[$field->get()]->getPrimitiveValue();
                });

                return $result;
            }
        })->setBindingParameterName('airports')->setReturnType(Lodata::getEntityType('airport')));

        $this->assertJsonResponse(
            Request::factory()
                ->path("/airports/\$filter(is_big eq true)/sorter(field='construction_date')")
        );
    }

    public function test_callback_bound_entity()
    {
        $this->withFlightModel();

        Lodata::add((new class('ffb1') extends Operation implements FunctionInterface {
            public function invoke(Entity $flight): Entity
            {
                return $flight;
            }
        })->setBindingParameterName('flight')->setReturnType(Lodata::getEntityType('flight')));

        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)/ffb1()')
        );
    }

    public function test_callback_bound_primitive()
    {
        $this->withFlightModel();

        Lodata::add((new class('ffb1') extends Operation implements FunctionInterface {
            public function invoke(String_ $origin): String_
            {
                return new String_(strtoupper($origin->get()));
            }
        })->setBindingParameterName('origin'));

        $this->assertJsonResponse(
            Request::factory()
                ->path('/flights(1)/origin/ffb1()')
        );
    }

    public function test_void_callback()
    {
        Lodata::add(new class('textv1') extends Operation implements FunctionInterface {
            public function invoke(): void
            {
            }
        });

        $this->assertInternalServerError(
            Request::factory()
                ->path('/textv1()')
        );
    }

    public function test_default_null_callback()
    {
        Lodata::add(new class('textv1') extends Operation implements FunctionInterface {
            public function invoke()
            {
            }
        });

        $this->assertInternalServerError(
            Request::factory()
                ->path('/textv1()')
        );
    }

    public function test_string_callback()
    {
        Lodata::add(new class('stringv1') extends Operation implements FunctionInterface {
            public function invoke(): string
            {
                return 'hello world';
            }
        });

        $this->assertMetadataDocuments();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/stringv1()')
        );
    }

    public function test_int_callback()
    {
        Lodata::add(new class('intv1') extends Operation implements FunctionInterface {
            public function invoke(): int
            {
                return 4;
            }
        });

        $this->assertMetadataDocuments();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/intv1()')
        );
    }

    public function test_float_callback()
    {
        Lodata::add(new class('floatv1') extends Operation implements FunctionInterface {
            public function invoke(): float
            {
                return 0.1;
            }
        });

        $this->assertMetadataDocuments();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/floatv1()')
        );
    }

    public function test_boolean_callback()
    {
        Lodata::add(new class('booleanv1') extends Operation implements FunctionInterface {
            public function invoke(): bool
            {
                return true;
            }
        });

        $this->assertMetadataDocuments();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/booleanv1()')
        );
    }

    public function test_bad_null_argument()
    {
        Lodata::add(new class('textv1') extends Operation implements FunctionInterface {
            public function invoke(String_ $a)
            {
            }
        });

        $this->assertBadRequest(
            Request::factory()
                ->path('/textv1()')
        );
    }

    public function test_bad_argument_type()
    {
        Lodata::add(new class('textv1') extends Operation implements FunctionInterface {
            public function invoke(String_ $a)
            {
            }
        });

        $this->assertBadRequest(
            Request::factory()
                ->path('/textv1(a=4)')
        );
    }

    public function test_string_argument()
    {
        Lodata::add(new class('stringv1') extends Operation implements FunctionInterface {
            public function invoke(string $arg): string
            {
                return $arg;
            }
        });

        $this->assertMetadataDocuments();

        $this->assertJsonResponse(
            Request::factory()
                ->path("/stringv1(arg='hello world')")
        );
    }

    public function test_int_argument()
    {
        Lodata::add(new class('intv1') extends Operation implements FunctionInterface {
            public function invoke(int $arg): int
            {
                return $arg;
            }
        });

        $this->assertMetadataDocuments();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/intv1(arg=4)')
        );
    }

    public function test_float_argument()
    {
        Lodata::add(new class('floatv1') extends Operation implements FunctionInterface {
            public function invoke(float $arg): float
            {
                return $arg;
            }
        });

        $this->assertMetadataDocuments();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/floatv1(arg=4.2)')
        );
    }

    public function test_boolean_argument()
    {
        Lodata::add(new class('booleanv1') extends Operation implements FunctionInterface {
            public function invoke(bool $arg): bool
            {
                return $arg;
            }
        });

        $this->assertMetadataDocuments();

        $this->assertJsonResponse(
            Request::factory()
                ->path('/booleanv1(arg=true)')
        );
    }

    public function test_null_argument()
    {
        Lodata::add(new class('booleanv1') extends Operation implements FunctionInterface {
            public function invoke(string $a, ?bool $arg, string $b): string
            {
                return $a.$b;
            }
        });

        $this->assertMetadataDocuments();

        $this->assertJsonResponse(
            Request::factory()
                ->path("/booleanv1(a='a',b='b')")
        );
    }
}