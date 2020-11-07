<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
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
use TypeError;

/**
 * Class Type
 * @method static PrimitiveType binary()
 * @method static PrimitiveType boolean()
 * @method static PrimitiveType byte()
 * @method static PrimitiveType date()
 * @method static PrimitiveType datetimeoffset()
 * @method static PrimitiveType decimal()
 * @method static PrimitiveType double()
 * @method static PrimitiveType duration()
 * @method static PrimitiveType guid()
 * @method static PrimitiveType int16()
 * @method static PrimitiveType int32()
 * @method static PrimitiveType int64()
 * @method static PrimitiveType sbyte()
 * @method static PrimitiveType single()
 * @method static PrimitiveType stream()
 * @method static PrimitiveType string()
 * @method static PrimitiveType timeofday()
 * @package Flat3\Lodata
 */
abstract class Type
{
    public static function __callStatic($name, $arguments): PrimitiveType
    {
        $resolver = [
            'binary' => Binary::class,
            'boolean' => Boolean::class,
            'byte' => Byte::class,
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

    abstract public function instance($value = null);
}
