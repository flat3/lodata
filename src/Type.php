<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
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
use Flat3\Lodata\Type\Stream;
use Flat3\Lodata\Type\String_;
use Flat3\Lodata\Type\TimeOfDay;
use TypeError;

/**
 * Type
 * @method static PrimitiveType binary() Binary type
 * @method static PrimitiveType boolean() Boolean type
 * @method static PrimitiveType byte() Byte type
 * @method static PrimitiveType collection() Collection type
 * @method static PrimitiveType date() Date type
 * @method static PrimitiveType datetimeoffset() DateTimeOffset type
 * @method static PrimitiveType decimal() Decimal type
 * @method static PrimitiveType double() Double type
 * @method static PrimitiveType duration() Duration type
 * @method static PrimitiveType guid() GUID type
 * @method static PrimitiveType int16() Int16 type
 * @method static PrimitiveType int32() Int32 type
 * @method static PrimitiveType int64() Int64 type
 * @method static PrimitiveType sbyte() SByte type
 * @method static PrimitiveType single() Single type
 * @method static PrimitiveType stream() Stream type
 * @method static PrimitiveType string() String type
 * @method static PrimitiveType timeofday() TimeOfDay type
 * @package Flat3\Lodata
 */
abstract class Type
{
    /**
     * Generate a new type container
     * @param  string  $name  Type name
     * @return PrimitiveType OData primitive type
     */
    public static function __callStatic($name, $arguments): PrimitiveType
    {
        $resolver = [
            'binary' => Binary::class,
            'boolean' => Boolean::class,
            'byte' => Byte::class,
            'collection' => Collection::class,
            'date' => Date::class,
            'datetimeoffset' => DateTimeOffset::class,
            'decimal' => Decimal::class,
            'double' => Double::class,
            'duration' => Duration::class,
            'guid' => Guid::class,
            'int16' => Int16::class,
            'int32' => Int32::class,
            'int64' => Int64::class,
            'sbyte' => SByte::class,
            'single' => Single::class,
            'stream' => Stream::class,
            'string' => String_::class,
            'timeofday' => TimeOfDay::class,
        ];

        if (!array_key_exists($name, $resolver)) {
            throw new InternalServerErrorException('invalid_type', 'An invalid type was requested: '.$name);
        }

        return new PrimitiveType($resolver[$name]);
    }

    /**
     * Cast a PHP type to an OData primitive
     * @param  string  $type  PHP type
     * @param  mixed  $value  Value
     * @return Primitive Primitive representation of type
     * @internal
     */
    public static function castInternalType(string $type, $value): Primitive
    {
        switch ($type) {
            case 'boolean':
                return new Boolean($value);

            case 'integer':
                return PHP_INT_SIZE === 8 ? new Int64($value) : new Int32($value);

            case 'float':
                return new Double($value);

            case 'string':
                return new String_($value);
        }

        throw new TypeError('Could not cast the provided internal type');
    }

    /**
     * Generate a new primitive instance of this type from the provided value
     * @param  mixed|null  $value  Value
     * @return Primitive OData primitive
     */
    abstract public function instance($value = null);

    /**
     * Render this type as an OpenAPI schema
     * @return array
     */
    abstract public function toOpenAPISchema(): array;
}
