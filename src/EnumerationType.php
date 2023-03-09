<?php

declare(strict_types=1);

namespace Flat3\Lodata;

use ArrayAccess;
use Flat3\Lodata\Exception\Protocol\ConfigurationException;
use Flat3\Lodata\Facades\Lodata;
use Flat3\Lodata\Helper\Discovery;
use Flat3\Lodata\Helper\EnumMember;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Traits\HasIdentifier;
use Flat3\Lodata\Type\Enum;
use Illuminate\Support\Str;

/**
 * Enumeration Type
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_EnumerationType
 * @package Flat3\Lodata
 */
class EnumerationType extends PrimitiveType implements ArrayAccess, IdentifierInterface
{
    use HasIdentifier;

    /**
     * Members of this enumeration type
     * @var EnumMember[]|ObjectArray $members
     */
    protected $members;

    /**
     * Whether this enumeration type supports multiple values being selected in instances of the type
     * @var bool $isFlags
     */
    protected $isFlags = false;

    public function __construct($identifier)
    {
        parent::__construct(Enum::class);
        $this->setIdentifier($identifier);
        $this->members = new ObjectArray();
        $this->setUnderlyingType(Type::int64());
    }

    /**
     * Set whether this enumerated type supports multiple members being selected in type instances
     * @param  bool  $isFlags
     * @return $this
     */
    public function setIsFlags(bool $isFlags = true): self
    {
        $this->isFlags = $isFlags;

        return $this;
    }

    /**
     * Get whether this enumerated type supports multiple members being selected in type instances
     * @return bool
     */
    public function getIsFlags(): bool
    {
        return $this->isFlags;
    }

    /**
     * Get the defined members of this enumerated type
     * @return EnumMember[]|ObjectArray Members of the type
     */
    public function getMembers(): ObjectArray
    {
        return $this->members;
    }

    /**
     * Whether a member exists on this type with the given name
     * @param  mixed  $offset  Member name
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $this->members->exists($offset);
    }

    /**
     * Get the member identified by the given name
     * @param  mixed  $offset  Member name
     */
    public function offsetGet($offset): ?EnumMember
    {
        return $this->members->get($offset);
    }

    /**
     * Add a new member of this enumerated type
     * @param $offset
     * @param  mixed  $value  Member name
     */
    public function offsetSet($offset, $value): void
    {
        $member = new EnumMember($this);

        if (null === $offset) {
            $member->setName($value);

            if ($this->members->isEmpty()) {
                $member->setValue(1);
            } else {
                $member->setValue($this->members->last()->getValue() * 2);
            }
        } else {
            $member->setName($offset);
            $member->setValue($value);
        }

        $this->members->add($member);
    }

    /**
     * Remove a member of the enumerated type
     * @param  mixed  $offset  Member name
     */
    public function offsetUnset($offset): void
    {
        $this->members->drop($offset);
    }

    /**
     * Create a new instance of the enumerated type
     * @param  null  $value  Member name
     * @return Enum
     */
    public function instance($value = 0): Primitive
    {
        return new Enum($this, $value);
    }

    /**
     * Get the OData enumeration type name for this class
     * @param  string  $enum  Enum class name
     * @return string OData identifier
     */
    public static function convertClassName(string $enum): string
    {
        return Str::pluralStudly(class_basename($enum));
    }

    /**
     * Convert the provided Enum class into an OData type
     * @param  string|Enum  $enum  Enum type
     * @return EnumerationType OData type
     */
    public static function discover($enum): EnumerationType
    {
        if (!self::isEnum($enum)) {
            throw new ConfigurationException('invalid_enum', 'The provided enum was not valid');
        }

        /** @var EnumerationType $type */
        $type = Lodata::getTypeDefinition(self::convertClassName($enum));

        if ($type instanceof EnumerationType) {
            return $type;
        }

        $type = new EnumerationType(self::convertClassName($enum));

        foreach ($enum::cases() as $case) {
            $type[$case->name] = $case->value;
        }

        if (defined($enum.'::isFlags')) {
            $type->setIsFlags($enum::isFlags);
        }

        Lodata::add($type);

        return $type;
    }

    /**
     * Determine whether the passed string represents an enum class
     * @param  string  $enum  Enum class
     * @return bool
     */
    public static function isEnum(string $enum): bool
    {
        return Discovery::supportsEnum() && enum_exists($enum);
    }
}
