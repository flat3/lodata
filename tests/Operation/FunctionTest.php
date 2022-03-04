<?php

namespace Flat3\Lodata\Tests\Operation;

use Flat3\Lodata\ComplexType;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Drivers\StaticEntitySet;
use Flat3\Lodata\Entity;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Operation;
use Flat3\Lodata\Tests\Drivers\WithNumericCollectionDriver;
use Flat3\Lodata\Tests\Helpers\Request;
use Flat3\Lodata\Tests\TestCase;
use Flat3\Lodata\Type;
use Flat3\Lodata\Type\Binary;
use Flat3\Lodata\Type\Boolean;
use Flat3\Lodata\Type\Byte;
use Flat3\Lodata\Type\Collection;
use Flat3\Lodata\Type\Date;
use Flat3\Lodata\Type\DateTimeOffset;
use Flat3\Lodata\Type\Decimal;
use Flat3\Lodata\Type\Double;
use Flat3\Lodata\Type\Duration;
use Flat3\Lodata\Type\Guid;
use Flat3\Lodata\Type\Int16;
use Flat3\Lodata\Type\Int32;
use Flat3\Lodata\Type\Int64;
use Flat3\Lodata\Type\SByte;
use Flat3\Lodata\Type\Single;
use Flat3\Lodata\Type\String_;
use Flat3\Lodata\Type\TimeOfDay;
use Flat3\Lodata\Type\UInt16;
use Flat3\Lodata\Type\UInt32;
use Flat3\Lodata\Type\UInt64;

class Airport extends Entity
{
}

class FunctionTest extends TestCase
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

    public function test_callback_no_parentheses()
    {
        $op = new Operation\Function_('exf1');
        $op->setCallable(function (): String_ {
            return new String_('hello');
        });
        Lodata::add($op);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/exf1')
        );
    }

    public function test_service_document()
    {
        $op = new Operation\Function_('exf1');
        $op->setCallable(function (): String_ {
            return new String_('hello');
        });
        Lodata::add($op);

        $this->assertJsonResponseSnapshot(
            (new Request)
        );
    }

    public function test_callback_entity()
    {
        $op = new Operation\Function_('exf3');
        $op->setCallable(function (String_ $code): Entity {
            $airport = new Airport();
            $airport->setType(Lodata::getEntityType('passenger'));
            $airport['code'] = $code->get();
            return $airport;
        });
        $op->setReturnType(Lodata::getEntityType('passenger'));
        Lodata::add($op);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/exf3(code='xyz')")
        );
    }

    public function test_callback_entity_set()
    {
        $this->withTextModel();

        $op = new Operation\Function_('textf1');
        $op->setCallable(function (EntitySet $texts): EntitySet {
            return $texts;
        });
        $op->setReturnType(Lodata::getEntityType('text'));
        Lodata::add($op);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/textf1()')
        );
    }

    public function test_with_arguments()
    {
        $this->withMathFunctions();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/add(a=3,b=4)')
        );
    }

    public function test_with_invalid_argument()
    {
        $this->withMathFunctions();

        $this->assertBadRequest(
            (new Request)
                ->path('/add(a=3,b=4,c=5)')
        );
    }

    public function test_with_argument_order()
    {
        $this->withMathFunctions();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/div(a=3,b=4)')
        );

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/div(b=3,a=4)')
        );
    }

    public function test_with_indirect_arguments()
    {
        $this->withMathFunctions();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/add(a=@c,b=@d)')
                ->query('@c', 1)
                ->query('@d', 2)
        );
    }

    public function test_with_single_indirect_argument()
    {
        $this->withMathFunctions();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/add(a=@c,b=@c)')
                ->query('@c', 1)
        );
    }

    public function test_with_missing_indirect_arguments()
    {
        $this->withMathFunctions();

        $this->assertBadRequest(
            (new Request)
                ->path('/add(a=@c,b=@e)')
                ->query('@c', 1)
                ->query('@d', 2)
        );
    }

    public function test_with_implicit_parameter_aliases()
    {
        $this->withMathFunctions();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/add')
                ->query('a', 1)
                ->query('b', 2)
        );
    }

    public function test_with_implicit_parameter_alias_matching_system_query_option()
    {
        $add = new Operation\Function_('add');
        $add->setCallable(function (Int32 $apply, Int32 $compute): Int32 {
            return new Int32($apply->get() + $compute->get());
        });
        Lodata::add($add);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/add')
                ->query('@apply', 1)
                ->query('@compute', 2)
        );
    }

    public function test_function_composition()
    {
        $identity = new Operation\Function_('identity');
        $identity->setCallable(function (Int32 $i): Int32 {
            return new Int32($i->get());
        });
        Lodata::add($identity);

        $increment = new Operation\Function_('increment');
        $increment->setCallable(function (Int32 $i): Int32 {
            return new Int32($i->get() + 1);
        });
        $increment->setBindingParameterName('i');
        Lodata::add($increment);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/identity(i=1)/increment/increment')
        );
    }

    public function test_callback_modified_flight_entity_set()
    {
        $ffn1 = new Operation\Function_('ffn1');
        $ffn1->setCallable(function (Transaction $transaction, EntitySet $passengers): EntitySet {
            $transaction->getSelect()->setValue('origin');
            return $passengers;
        });
        $ffn1->setReturnType(Lodata::getEntityType('passenger'));
        Lodata::add($ffn1);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/ffn1()')
        );
    }

    public function test_callback_bound_entity_set()
    {
        $ffb1 = new Operation\Function_('ffb1');
        $ffb1->setCallable(function (EntitySet $passengers): EntitySet {
            return $passengers;
        });
        $ffb1->setBindingParameterName('passengers');
        $ffb1->setReturnType(Lodata::getEntityType('passenger'));
        Lodata::add($ffb1);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/passengers/ffb1()')
        );
    }

    public function test_callback_bound_entity_set_with_filter()
    {
        $sorter = new Operation\Function_('sorter');
        $sorter->setCallable(function (String_ $field, EntitySet $passengers): Collection {
            $result = new Collection();
            $result->setUnderlyingType($passengers->getType());

            foreach ($passengers->query() as $airport) {
                $result[] = $airport;
            }

            $result->get()->sort(function (Entity $a1, Entity $a2) use ($field) {
                return $a1[$field->get()]->getPrimitiveValue() <=> $a2[$field->get()]->getPrimitiveValue();
            });

            return $result;
        });

        $sorter->setBindingParameterName('passengers');
        $sorter->setReturnType(Lodata::getEntityType('passenger'));
        Lodata::add($sorter);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/passengers/\$filter(chips eq true)/sorter(field='dob')")
        );
    }

    public function test_callback_bound_entity()
    {
        $ffb1 = new Operation\Function_('ffb1');
        $ffb1->setCallable(function (Entity $passenger): Entity {
            return $passenger;
        });
        $ffb1->setBindingParameterName('passenger');
        $ffb1->setReturnType(Lodata::getEntityType('passenger'));
        Lodata::add($ffb1);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/passengers(1)/ffb1()')
        );
    }

    public function test_callback_bound_primitive()
    {
        $ffb1 = new Operation\Function_('ffb1');
        $ffb1->setCallable(function (string $name): string {
            return strtoupper($name);
        });
        $ffb1->setBindingParameterName('name');
        Lodata::add($ffb1);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/passengers(1)/name/ffb1()')
        );
    }

    public function test_callback_bound_internal_type()
    {
        $identity = new Operation\Function_('id');
        $identity->setCallable(function (int $i): int {
            return $i;
        });
        Lodata::add($identity);

        $increment = new Operation\Function_('incr');
        $increment->setCallable(function (int $a): int {
            return $a + 1;
        });
        $increment->setBindingParameterName('a');
        Lodata::add($increment);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/id(i=1)/incr')
        );
    }

    public function test_void_callback()
    {
        $textv1 = new Operation\Function_('textv1');
        $textv1->setCallable(function (): void {
        });
        Lodata::add($textv1);

        $this->assertInternalServerError(
            (new Request)
                ->path('/textv1()')
        );
    }

    public function test_default_null_callback()
    {
        $textv1 = new Operation\Function_('textv1');
        $textv1->setCallable(function () {
        });
        Lodata::add($textv1);

        $this->assertInternalServerError(
            (new Request)
                ->path('/textv1()')
        );
    }

    public function test_string_callback()
    {
        $stringv1 = new Operation\Function_('stringv1');
        $stringv1->setCallable(function (): string {
            return 'hello world';
        });
        Lodata::add($stringv1);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/stringv1()')
        );
    }

    public function test_int_callback()
    {
        $intv1 = new Operation\Function_('intv1');
        $intv1->setCallable(function (): int {
            return 4;
        });
        Lodata::add($intv1);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/intv1()')
        );
    }

    public function test_float_callback()
    {
        $floatv1 = new Operation\Function_('floatv1');
        $floatv1->setCallable(function (): float {
            return 0.1;
        });
        Lodata::add($floatv1);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/floatv1()')
        );
    }

    public function test_boolean_callback()
    {
        $booleanv1 = new Operation\Function_('booleanv1');
        $booleanv1->setCallable(function (): bool {
            return true;
        });
        Lodata::add($booleanv1);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/booleanv1()')
        );
    }

    public function test_bad_null_argument()
    {
        $textv1 = new Operation\Function_('textv1');
        $textv1->setCallable(function (String_ $a) {
        });
        Lodata::add($textv1);

        $this->assertBadRequest(
            (new Request)
                ->path('/textv1()')
        );
    }

    public function test_bad_argument_type()
    {
        $textv1 = new Operation\Function_('textv1');
        $textv1->setCallable(function (String_ $a) {
        });
        Lodata::add($textv1);

        $this->assertBadRequest(
            (new Request)
                ->path('/textv1(a=4)')
        );
    }

    public function test_string_argument()
    {
        $stringv1 = new Operation\Function_('stringv1');
        $stringv1->setCallable(function (string $arg): string {
            return $arg;
        });
        Lodata::add($stringv1);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/stringv1(arg='hello world')")
        );
    }

    public function test_int_argument()
    {
        $intv1 = new Operation\Function_('intv1');
        $intv1->setCallable(function (int $arg): int {
            return $arg;
        });
        Lodata::add($intv1);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/intv1(arg=4)')
        );
    }

    public function test_array_argument()
    {
        $arrayv1 = new Operation\Function_('arrayv1');
        $arrayv1->setCallable(function (array $args): array {
            return $args;
        });
        Lodata::add($arrayv1);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/arrayv1(args=@c)')
                ->query('@c', '["q", 4]')
        );
    }

    public function test_float_argument()
    {
        $floatv1 = new Operation\Function_('floatv1');
        $floatv1->setCallable(function (float $arg): float {
            return $arg;
        });
        Lodata::add($floatv1);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/floatv1(arg=4.2)')
        );
    }

    public function test_boolean_argument()
    {
        $booleanv1 = new Operation\Function_('booleanv1');
        $booleanv1->setCallable(function (bool $arg): bool {
            return $arg;
        });
        Lodata::add($booleanv1);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path('/booleanv1(arg=true)')
        );
    }

    public function test_null_argument()
    {
        $booleanv1 = new Operation\Function_('booleanv1');
        $booleanv1->setCallable(function (string $a, ?bool $arg, string $b): string {
            return $a.$b;
        });
        Lodata::add($booleanv1);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/booleanv1(a='a',b='b')")
        );
    }

    public function test_odata_binary_argument()
    {
        $op = new Operation\Function_('op');
        $op->setCallable(function (Binary $arg): Binary {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/op(arg=aGVsbG8gd29ybGQ=)")
        );
    }

    public function test_odata_boolean_argument()
    {
        $op = new Operation\Function_('op');
        $op->setCallable(function (Boolean $arg): Boolean {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/op(arg=false)")
        );
    }

    public function test_odata_byte_argument()
    {
        $op = new Operation\Function_('op');
        $op->setCallable(function (Byte $arg): Byte {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/op(arg=4)")
        );
    }

    public function test_odata_collection_argument()
    {
        $op = new Operation\Function_('op');
        $op->setCallable(function (Collection $args): Collection {
            $args->setUnderlyingType(Type::string());

            return $args;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/op(args=@c)")
                ->query('@c', '["red","green"]')
        );
    }

    public function test_odata_collection_argument_with_type()
    {
        $complexType = new ComplexType('tmp');
        $complexType->addDeclaredProperty('a', Type::string());
        $complexType->addDeclaredProperty('b', Type::int64());
        $type = Type::collection($complexType);
        Lodata::add($complexType);

        $op = new Operation\Function_('op');
        $op->setCallable(function (Collection $args): Collection {
            /** @var Collection $collection */
            $collection = (new Collection)->setUnderlyingType(Lodata::getTypeDefinition('tmp'))->set([
                ['a' => 'a', 'b' => 4],
            ]);

            return $collection;
        })->setReturnType($type);
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/op(args=@c)")
                ->query('@c', '["red","green"]')
        );
    }

    public function test_odata_date_argument()
    {
        $op = new Operation\Function_('op');
        $op->setCallable(function (Date $arg): Date {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/op(arg=2020-01-01)")
        );
    }

    public function test_odata_datetimeoffset_argument()
    {
        $op = new Operation\Function_('op');
        $op->setCallable(function (DateTimeOffset $arg): DateTimeOffset {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/op(arg=2020-01-01T23:23:23+00:01)")
        );
    }

    public function test_odata_decimal_argument()
    {
        $op = new Operation\Function_('op');
        $op->setCallable(function (Decimal $arg): Decimal {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/op(arg=3.14)")
        );
    }

    public function test_odata_double_argument()
    {
        $op = new Operation\Function_('op');
        $op->setCallable(function (Double $arg): Double {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/op(arg=3.14)")
        );
    }

    public function test_odata_duration_argument()
    {
        $op = new Operation\Function_('op');
        $op->setCallable(function (Duration $arg): Duration {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/op(arg=P4DT6H4M45.121999999974S)")
        );
    }

    public function test_odata_guid_argument()
    {
        $op = new Operation\Function_('op');
        $op->setCallable(function (Guid $arg): Guid {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/op(arg=2D1B80E8-0DAD-4EE7-AB6F-AE9FEC896290)")
        );
    }

    public function test_odata_int16_argument()
    {
        $op = new Operation\Function_('op');
        $op->setCallable(function (Int16 $arg): Int16 {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/op(arg=-32767)")
        );
    }

    public function test_odata_uint16_argument()
    {
        $op = new Operation\Function_('op');
        $op->setCallable(function (UInt16 $arg): UInt16 {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/op(arg=32767)")
        );
    }

    public function test_odata_int32_argument()
    {
        $op = new Operation\Function_('op');
        $op->setCallable(function (Int32 $arg): Int32 {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/op(arg=-2147483647)")
        );
    }

    public function test_odata_uint32_argument()
    {
        $op = new Operation\Function_('op');
        $op->setCallable(function (UInt32 $arg): UInt32 {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/op(arg=2147483647)")
        );
    }

    public function test_odata_int64_argument()
    {
        $op = new Operation\Function_('op');
        $op->setCallable(function (Int64 $arg): Int64 {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/op(arg=-9223372036854775807)")
        );
    }

    public function test_odata_uint64_argument()
    {
        $op = new Operation\Function_('op');
        $op->setCallable(function (UInt64 $arg): UInt64 {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/op(arg=9223372036854775807)")
        );
    }

    public function test_odata_sbyte_argument()
    {
        $op = new Operation\Function_('op');
        $op->setCallable(function (SByte $arg): SByte {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/op(arg=64)")
        );
    }

    public function test_odata_single_argument()
    {
        $op = new Operation\Function_('op');
        $op->setCallable(function (Single $arg): Single {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/op(arg=-3.14)")
        );
    }

    public function test_odata_string_argument()
    {
        $op = new Operation\Function_('op');
        $op->setCallable(function (String_ $arg): String_ {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/op(arg='hello, world!')")
        );
    }

    public function test_odata_timeofday_argument()
    {
        $op = new Operation\Function_('op');
        $op->setCallable(function (TimeOfDay $arg): TimeOfDay {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->path("/op(arg=23:23:23)")
        );
    }
}