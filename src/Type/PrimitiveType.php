<?php

namespace Flat3\OData\Type;

use RuntimeException;

/**
 * Class PrimitiveType
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
abstract class PrimitiveType
{
    public const EDM_TYPE = 'Edm.None';

    public const URL_NULL = 'null';
    public const URL_TRUE = 'true';
    public const URL_FALSE = 'false';

    /** @var ?mixed $value Internal representation of the value */
    protected $value;

    /** @var bool $nullable Whether the value can be made null */
    protected $nullable = true;

    public function __construct($value, bool $nullable = true)
    {
        $this->nullable = $nullable;
        $this->toInternal($value);
    }

    /**
     * Convert the provided value to the internal representation
     *
     * @param $value
     */
    abstract public function toInternal($value): void;

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public static function factory($value = null, ?bool $nullable = true): self
    {
        if ($value instanceof PrimitiveType) {
            return $value;
        }

        return new static($value, $nullable);
    }

    /**
     * Get the EDM name of this type
     *
     * @return string
     */
    public function getEdmTypeName()
    {
        return $this::EDM_TYPE;
    }

    /**
     * Get the internal representation of the value
     *
     * @return mixed
     */
    public function getInternalValue()
    {
        return $this->value;
    }

    /**
     * Get the value as OData URL encoded
     *
     * @return string
     */
    abstract public function toUrl(): string;

    /**
     * Get the value as suitable for IEEE754 JSON encoding
     *
     * @return string
     */
    public function toJsonIeee754(): ?string
    {
        $value = $this->toJson();

        return null === $value ? null : (string) $value;
    }

    /**
     * Get the value as suitable for JSON encoding
     *
     * @return mixed
     */
    abstract public function toJson();

    /**
     * Return null or an empty value if this type cannot be made null
     *
     * @param $value
     *
     * @return mixed
     */
    public function maybeNull($value)
    {
        if (null === $value) {
            return $this->nullable ? null : $this->getEmpty();
        }

        return $value;
    }

    protected function getEmpty()
    {
        return '';
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
