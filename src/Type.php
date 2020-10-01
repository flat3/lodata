<?php

namespace Flat3\OData;

use Flat3\OData\Interfaces\TypeInterface;
use Flat3\OData\Type\Binary;
use Flat3\OData\Type\Boolean;
use Flat3\OData\Type\Byte;
use Flat3\OData\Type\Date;
use Flat3\OData\Type\DateTimeOffset;
use Flat3\OData\Type\Decimal;
use Flat3\OData\Type\Double;
use Flat3\OData\Type\Duration;
use Flat3\OData\Type\Guid;
use Flat3\OData\Type\Int16;
use Flat3\OData\Type\Int32;
use Flat3\OData\Type\Int64;
use Flat3\OData\Type\SByte;
use Flat3\OData\Type\Single;
use Flat3\OData\Type\Stream;
use Flat3\OData\Type\String_;
use Flat3\OData\Type\TimeOfDay;
use RuntimeException;

/**
 * Class Type
 * @method static Binary binary()
 * @method static Boolean boolean()
 * @method static Byte byte()
 * @method static Date date()
 * @method static DateTimeOffset datetimeoffset()
 * @method static Decimal decimal()
 * @method static Double double()
 * @method static Duration duration()
 * @method static Guid guid()
 * @method static Int16 int16()
 * @method static Int32 int32()
 * @method static Int64 int64()
 * @method static SByte sbyte()
 * @method static Single single()
 * @method static Stream stream()
 * @method static String_ string()
 * @method static TimeOfDay timeofday()
 * @package Flat3\OData
 */
abstract class Type
{
    protected $name = 'Edm.None';

    public function getName(): string
    {
        return $this->name;
    }

    public static function __callStatic($name, $arguments)
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
            throw new RuntimeException('An invalid type was requested: '.$name);
        }

        $clazz = $resolver[$name];
        return new $clazz(null, true);
    }
}
