<?php

namespace Flat3\OData;

abstract class Type
{
    public const EDM_TYPE = 'Edm.None';

    public const URL_NULL = 'null';
    public const URL_TRUE = 'true';
    public const URL_FALSE = 'false';

    /** @var Type[] $identity */
    protected static $identity = [];

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

    /**
     * @return self
     */
    public static function type(): self
    {
        if (array_key_exists(static::class, static::$identity)) {
            return static::$identity[static::class];
        }

        static::$identity[static::class] = new static(null);

        return static::$identity[static::class];
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function factory($value = null, ?bool $nullable = true): self
    {
        if ($value instanceof Type) {
            return $value;
        }

        return new $this($value, $nullable);
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
}
