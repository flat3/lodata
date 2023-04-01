<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use Carbon\CarbonImmutable;
use DateTime;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Helper\CollectionType;
use Flat3\Lodata\Type\Binary;
use Flat3\Lodata\Type\Boolean;
use Flat3\Lodata\Type\Byte;
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
use Flat3\Lodata\Type\Stream;
use Flat3\Lodata\Type\String_;
use Flat3\Lodata\Type\TimeOfDay;
use Flat3\Lodata\Type\UInt16;
use Flat3\Lodata\Type\UInt32;
use Flat3\Lodata\Type\UInt64;
use Flat3\Lodata\Type\Untyped;
use Illuminate\Support\Arr;
use TypeError;

/**
 * Type
 * @method static PrimitiveType binary() Edm.Binary
 * @method static PrimitiveType boolean() Edm.Boolean
 * @method static PrimitiveType byte() Edm.Byte
 * @method static CollectionType collection(?Type $type = null) Collection()
 * @method static PrimitiveType date() Edm.Date
 * @method static PrimitiveType datetimeoffset() Edm.DateTimeOffset
 * @method static PrimitiveType decimal() Edm.Decimal
 * @method static PrimitiveType double() Edm.Double
 * @method static PrimitiveType duration() Edm.Duration
 * @method static EnumerationType enum(string $identifier) Edm.EnumType
 * @method static PrimitiveType guid() Edm.Guid
 * @method static PrimitiveType int16() Edm.Int16
 * @method static PrimitiveType int32() Edm.Int32
 * @method static PrimitiveType int64() Edm.Int64
 * @method static PrimitiveType sbyte() Edm.SByte
 * @method static PrimitiveType single() Edm.Single
 * @method static PrimitiveType stream() Edm.Stream
 * @method static PrimitiveType string() Edm.String
 * @method static PrimitiveType timeofday() Edm.TimeOfDay
 * @method static PrimitiveType uint16() UInt16
 * @method static PrimitiveType uint32() UInt32
 * @method static PrimitiveType uint64() UInt64
 * @method static Untyped untyped() Edm.Untyped
 * @package Flat3\Lodata
 */
abstract class Type
{
    /**
     * The factory class name to generate instances of this type
     * @var Primitive|string $factory Factory class
     */
    protected $factory;

    /**
     * Generate a new type container
     * @param  string  $name  Type name
     * @return PrimitiveType|ComplexType OData type
     */
    public static function __callStatic($name, $arguments): Type
    {
        switch ($name) {
            case 'binary':
                return new PrimitiveType(Binary::class);

            case 'boolean':
                return new PrimitiveType(Boolean::class);

            case 'byte':
                return new PrimitiveType(Byte::class);

            case 'collection':
                return new CollectionType(...$arguments);

            case 'date':
                return new PrimitiveType(Date::class);

            case 'datetimeoffset':
                return new PrimitiveType(DateTimeOffset::class);

            case 'decimal':
                return new PrimitiveType(Decimal::class);

            case 'double':
                return new PrimitiveType(Double::class);

            case 'duration':
                return new PrimitiveType(Duration::class);

            case 'enum':
                return new EnumerationType(...$arguments);

            case 'guid':
                return new PrimitiveType(Guid::class);

            case 'int16':
                return new PrimitiveType(Int16::class);

            case 'int32':
                return new PrimitiveType(Int32::class);

            case 'int64':
                return new PrimitiveType(Int64::class);

            case 'sbyte':
                return new PrimitiveType(SByte::class);

            case 'single':
                return new PrimitiveType(Single::class);

            case 'stream':
                return new PrimitiveType(Stream::class);

            case 'string':
                return new PrimitiveType(String_::class);

            case 'timeofday':
                return new PrimitiveType(TimeOfDay::class);

            case 'uint16':
                return new PrimitiveType(UInt16::class);

            case 'uint32':
                return new PrimitiveType(UInt32::class);

            case 'uint64':
                return new PrimitiveType(UInt64::class);

            case 'untyped':
                return new Untyped();
        }

        throw new InternalServerErrorException('invalid_type', 'An invalid type was requested: '.$name);
    }

    /**
     * Generate a new primitive instance of this type from the provided value
     * @param  mixed|null  $value  Value
     * @return Primitive OData primitive
     */
    abstract public function instance($value = null);

    /**
     * Return a type object based on the provided value
     * @param  mixed  $value  PHP value
     * @return Type OData type representation
     */
    public static function fromInternalValue($value): Type
    {
        if ($value instanceof ComplexValue) {
            return $value->getType();
        }

        if ($value instanceof Primitive) {
            return new PrimitiveType(get_class($value));
        }

        if (is_object($value)) {
            return self::fromInternalType(get_class($value));
        }

        if (is_array($value)) {
            return Arr::isAssoc($value) ? self::untyped() : self::collection();
        }

        return self::fromInternalType(gettype($value));
    }

    /**
     * Cast a PHP type to an OData type
     * @param  string  $type  PHP type
     * @return Type OData type representation
     */
    public static function fromInternalType(string $type): Type
    {
        switch ($type) {
            case 'void':
            case 'string':
            case 'NULL':
                return self::string();

            case 'float':
            case 'double':
                return self::double();

            case 'int':
            case 'integer':
                return self::int64();

            case 'bool':
            case 'boolean':
                return self::boolean();

            case CarbonImmutable::class:
            case is_a($type, DateTime::class, true):
                return self::datetimeoffset();

            case 'array':
                return self::collection();

            case 'stdClass':
                return new Untyped();
        }

        throw new TypeError('Could not cast the provided internal type');
    }

    /**
     * Return whether the provided class name represents instances of this type
     * @param  string  $class
     * @return bool
     */
    public function is(string $class): bool
    {
        return is_a($this->factory, $class, true);
    }
}
