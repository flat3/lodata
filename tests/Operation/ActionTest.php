<?php

namespace Flat3\Lodata\Tests\Operation;

use Carbon\CarbonImmutable;
use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
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

class ActionTest extends TestCase
{
    use WithNumericCollectionDriver;

    public function test_get_not_allowed()
    {
        $exa1 = new Operation\Action('exa1');
        $exa1->setCallable(function (): String_ {
            return new String_('hello');
        });
        Lodata::add($exa1);

        $this->assertMethodNotAllowed(
            (new Request)
                ->path('/exa1()')
        );
    }

    public function test_callback()
    {
        $exa1 = new Operation\Action('exa1');
        $exa1->setCallable(function (): String_ {
            return new String_('hello');
        });
        Lodata::add($exa1);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->path('/exa1()')
        );
    }

    public function test_service_document()
    {
        $exa1 = new Operation\Action('exa1');
        $exa1->setCallable(function (): String_ {
            return new String_('hello');
        });
        Lodata::add($exa1);

        $this->assertJsonResponseSnapshot(
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
        $textv1 = new Operation\Action('textv1');
        $textv1->setCallable(function (): Int32 {
            return new Int32(3);
        });
        Lodata::add($textv1);

        $this->assertBadRequest(
            (new Request)
                ->post()
                ->path('/textv1()/$value')
        );
    }

    public function test_void_callback()
    {
        $textv1 = new Operation\Action('textv1');
        $textv1->setCallable(function (): void {
        });
        Lodata::add($textv1);

        $this->assertNoContent(
            (new Request)
                ->post()
                ->path('/textv1()')
        );
    }

    public function test_default_null_callback()
    {
        $textv1 = new Operation\Action('textv1');
        $textv1->setCallable(function () {
        });
        Lodata::add($textv1);

        $this->assertNoContent(
            (new Request)
                ->post()
                ->path('/textv1()')
        );
    }

    public function test_explicit_null_callback()
    {
        $textv1 = new Operation\Action('textv1');
        $textv1->setCallable(function () {
            return null;
        });
        Lodata::add($textv1);

        $this->assertNoContent(
            (new Request)
                ->post()
                ->path('/textv1()')
        );
    }

    public function test_bound()
    {
        $aa1 = new Operation\Action('aa1');
        $aa1->setCallable(function (Entity $passenger): Entity {
            return $passenger;
        });
        $aa1->setBindingParameterName('passenger');
        $aa1->setReturnType(Lodata::getEntityType('passenger'));
        Lodata::add($aa1);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->path('/passengers(1)/aa1')
        );
    }

    public function test_bound_with_parameters()
    {
        $aa1 = new Operation\Action('aa1');
        $aa1->setCallable(function (Entity $passenger, string $a): string {
            return $passenger['name']->getPrimitiveValue().$a;
        });
        $aa1->setBindingParameterName('passenger');
        Lodata::add($aa1);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->body(
                    [
                        'a' => 'world'
                    ]
                )
                ->path('/passengers(1)/aa1')
        );
    }

    public function test_create()
    {
        $aa1 = new Operation\Action('aa1');
        $aa1->setCallable(function (EntitySet $passengers, Transaction $transaction): Entity {
            $transaction->getResponse()->setStatusCode(Response::HTTP_CREATED);

            $entity = $passengers->newEntity();
            $entity->setEntityId(4);

            return $entity;
        });
        $aa1->setReturnType(Lodata::getEntityType('passenger'));
        Lodata::add($aa1);

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->path('/passengers/aa1'),
            Response::HTTP_CREATED
        );
    }

    public function test_parameters()
    {
        $aa1 = new Operation\Action('aa1');
        $aa1->setCallable(function (Int32 $a, Int32 $b): Int32 {
            return new Int32($a->get() + $b->get());
        });
        Lodata::add($aa1);

        $this->assertJsonResponseSnapshot(
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
        $aa1 = new Operation\Action('aa1');
        $aa1->setCallable(function (int $a, int $b): Int32 {
            return new Int32(99);
        });
        Lodata::add($aa1);

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
        $aa1 = new Operation\Action('aa1');
        $aa1->setCallable(function (Int32 $a, Int32 $b): Int32 {
            return new Int32($a->get() + $b->get());
        });
        Lodata::add($aa1);

        $this->assertNotAcceptable(
            (new Request)
                ->post()
                ->body('[d')
                ->path('/aa1')
        );
    }

    public function test_parameters_invalid_body_array()
    {
        $aa1 = new Operation\Action('aa1');
        $aa1->setCallable(function (Int32 $a, Int32 $b): Int32 {
            return new Int32($a->get() + $b->get());
        });
        Lodata::add($aa1);

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
        $booleanv1 = new Operation\Action('booleanv1');
        $booleanv1->setCallable(function (): ?bool {
            return null;
        });
        Lodata::add($booleanv1);

        $this->assertMetadataSnapshot();

        $this->assertNoContent(
            (new Request)
                ->post()
                ->path('/booleanv1()')
        );
    }

    public function test_array_argument()
    {
        $arrayv1 = new Operation\Action('arrayv1');
        $arrayv1->setCallable(function (array $args): array {
            return $args;
        });
        Lodata::add($arrayv1);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->path('/arrayv1')
                ->body(['args' => ["q", 4]])
        );
    }

    public function test_date_argument()
    {
        $datev1 = new Operation\Action('datev1');
        $datev1->setCallable(function (CarbonImmutable $dt): string {
            return $dt->dayName;
        });
        Lodata::add($datev1);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->body([
                    'dt' => '2020-01-01T23:23:23+00:01'
                ])
                ->path("/datev1")
        );
    }

    public function test_odata_binary_argument()
    {
        $op = new Operation\Action('op');
        $op->setCallable(function (Binary $arg): Binary {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->body([
                    'arg' => 'aGVsbG8gd29ybGQ=',
                ])
                ->path("/op")
        );
    }

    public function test_odata_boolean_argument()
    {
        $op = new Operation\Action('op');
        $op->setCallable(function (Boolean $arg): Boolean {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->body([
                    'arg' => false,
                ])
                ->path("/op")
        );
    }

    public function test_odata_byte_argument()
    {
        $op = new Operation\Action('op');
        $op->setCallable(function (Byte $arg): Byte {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->body([
                    'arg' => 4,
                ])
                ->path("/op")
        );
    }

    public function test_odata_collection_argument()
    {
        $op = new Operation\Action('op');
        $op->setCallable(function (Collection $args): Collection {
            $args->setUnderlyingType(Type::string());

            return $args;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->path("/op")
                ->body(['args' => ["red", "green"]])
        );
    }

    public function test_odata_date_argument()
    {
        $op = new Operation\Action('op');
        $op->setCallable(function (Date $arg): Date {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->body([
                    'arg' => '2020-01-01',
                ])
                ->path("/op")
        );
    }

    public function test_odata_datetimeoffset_argument()
    {
        $op = new Operation\Action('op');
        $op->setCallable(function (DateTimeOffset $arg): DateTimeOffset {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->body([
                    'arg' => '2020-01-01T23:23:23+00:01',
                ])
                ->path("/op")
        );
    }

    public function test_odata_decimal_argument()
    {
        $op = new Operation\Action('op');
        $op->setCallable(function (Decimal $arg): Decimal {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->body([
                    'arg' => 3.14,
                ])
                ->path("/op")
        );
    }

    public function test_odata_double_argument()
    {
        $op = new Operation\Action('op');
        $op->setCallable(function (Double $arg): Double {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->body([
                    'arg' => 3.14,
                ])
                ->path("/op")
        );
    }

    public function test_odata_duration_argument()
    {
        $op = new Operation\Action('op');
        $op->setCallable(function (Duration $arg): Duration {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->body([
                    'arg' => 'P4DT6H4M45.121999999974S',
                ])
                ->path("/op")
        );
    }

    public function test_odata_guid_argument()
    {
        $op = new Operation\Action('op');
        $op->setCallable(function (Guid $arg): Guid {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->body([
                    'arg' => '2D1B80E8-0DAD-4EE7-AB6F-AE9FEC896290',
                ])
                ->path("/op")
        );
    }

    public function test_odata_int16_argument()
    {
        $op = new Operation\Action('op');
        $op->setCallable(function (Int16 $arg): Int16 {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->body([
                    'arg' => -32767,
                ])
                ->path("/op")
        );
    }

    public function test_odata_uint16_argument()
    {
        $op = new Operation\Action('op');
        $op->setCallable(function (UInt16 $arg): UInt16 {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->body([
                    'arg' => 32767,
                ])
                ->path("/op")
        );
    }

    public function test_odata_int32_argument()
    {
        $op = new Operation\Action('op');
        $op->setCallable(function (Int32 $arg): Int32 {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->body([
                    'arg' => -2147483647,
                ])
                ->path("/op")
        );
    }

    public function test_odata_uint32_argument()
    {
        $op = new Operation\Action('op');
        $op->setCallable(function (UInt32 $arg): UInt32 {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->body([
                    'arg' => 2147483647,
                ])
                ->path("/op")
        );
    }

    public function test_odata_int64_argument()
    {
        $op = new Operation\Action('op');
        $op->setCallable(function (Int64 $arg): Int64 {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->body([
                    'arg' => -9223372036854775807,
                ])
                ->path("/op")
        );
    }

    public function test_odata_uint64_argument()
    {
        $op = new Operation\Action('op');
        $op->setCallable(function (UInt64 $arg): UInt64 {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->body([
                    'arg' => 9223372036854775807,
                ])
                ->path("/op")
        );
    }

    public function test_odata_sbyte_argument()
    {
        $op = new Operation\Action('op');
        $op->setCallable(function (SByte $arg): SByte {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->body([
                    'arg' => 64,
                ])
                ->path("/op")
        );
    }

    public function test_odata_single_argument()
    {
        $op = new Operation\Action('op');
        $op->setCallable(function (Single $arg): Single {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->body([
                    'arg' => -3.14,
                ])
                ->path("/op")
        );
    }

    public function test_odata_string_argument()
    {
        $op = new Operation\Action('op');
        $op->setCallable(function (String_ $arg): String_ {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->body([
                    'arg' => 'hello, world!',
                ])
                ->path("/op")
        );
    }

    public function test_odata_timeofday_argument()
    {
        $op = new Operation\Action('op');
        $op->setCallable(function (TimeOfDay $arg): TimeOfDay {
            return $arg;
        });
        Lodata::add($op);

        $this->assertMetadataSnapshot();

        $this->assertJsonResponseSnapshot(
            (new Request)
                ->post()
                ->body([
                    'arg' => '23:23:23',
                ])
                ->path("/op")
        );
    }
}