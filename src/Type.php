<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Type\Binary;
use Flat3\Lodata\Type\Boolean;
use Flat3\Lodata\Type\Byte;
use Flat3\Lodata\Type\Date;
use Flat3\Lodata\Type\DateTimeOffset;
use Flat3\Lodata\Type\Decimal;
use Flat3\Lodata\Type\Double;
use Flat3\Lodata\Type\Duration;
use Flat3\Lodata\Type\Enum;
use Flat3\Lodata\Type\Guid;
use Flat3\Lodata\Type\Int16;
use Flat3\Lodata\Type\Int32;
use Flat3\Lodata\Type\Int64;
use Flat3\Lodata\Type\SByte;
use Flat3\Lodata\Type\Single;
use Flat3\Lodata\Type\Stream;
use Flat3\Lodata\Type\String_;
use Flat3\Lodata\Type\TimeOfDay;

/**
 * Class TypeDefinition
 * @method static self binary()
 * @method static self boolean()
 * @method static self byte()
 * @method static self date()
 * @method static self datetimeoffset()
 * @method static self decimal()
 * @method static self double()
 * @method static self duration()
 * @method static self enum()
 * @method static self guid()
 * @method static self int16()
 * @method static self int32()
 * @method static self int64()
 * @method static self sbyte()
 * @method static self single()
 * @method static self stream()
 * @method static self string()
 * @method static self timeofday()
 * @package Flat3\OData
 */
abstract class Type
{
    public static function __callStatic($name, $arguments): self
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
            'enum' => Enum::class,
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

    abstract public function instance($value = null);
}
