<?php

namespace Flat3\Lodata;

use ArrayAccess;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Traits\HasIdentifier;
use Flat3\Lodata\Type\Enum;
use Flat3\Lodata\Type\EnumMember;

/**
 * Enumeration Type
 * @link https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#_Toc38530376
 * @package Flat3\Lodata
 */
class EnumerationType extends PrimitiveType implements ArrayAccess
{
    use HasIdentifier;

    /**
     * Members of this enumeration type
     * @var ObjectArray $members
     * @internal
     */
    protected $members;

    /**
     * The underlying type of this enumeration type
     * @var PrimitiveType $underlyingType
     * @internal
     */
    protected $underlyingType;

    /**
     * Whether this enumeration type supports multiple values being selected in instances of the type
     * @var bool $isFlags
     */
    protected $isFlags = false;

    public function __construct($identifier)
    {
        $this->setIdentifier($identifier);
        $this->members = new ObjectArray();
        $this->underlyingType = Type::int32();
        parent::__construct(Enum::class);
    }

    /**
     * Generate a new enumerated type
     * @param  string  $identifier  Type name
     * @return static
     */
    public static function factory(string $identifier): self
    {
        return new self($identifier);
    }

    /**
     * Get the underlying type of this enumerated type
     * @return Type Underlying type
     */
    public function getUnderlyingType(): Type
    {
        return $this->underlyingType;
    }

    /**
     * Set the underlying type of this enumerated type
     * @param  Type  $type  Underlying type
     * @return $this
     */
    public function setUnderlyingType(Type $type): self
    {
        $this->underlyingType = $type;

        return $this;
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
     * @return ObjectArray Members of the type
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
    public function offsetExists($offset)
    {
        return $this->members->exists($offset);
    }

    /**
     * Get the member identified by the given name
     * @param  mixed  $offset  Member name
     * @return EnumMember|null
     */
    public function offsetGet($offset)
    {
        return $this->members->get($offset);
    }

    /**
     * Add a new member of this enumerated type
     * @param  mixed  $offset  Member name
     * @param  mixed  $member  Member value
     */
    public function offsetSet($offset, $member)
    {
        if (!is_object($offset) || !$this->underlyingType->is(get_class($offset))) {
            $offset = $this->underlyingType->instance($offset ? $offset : count($this->members) + 1);
        }

        if (!$member instanceof EnumMember) {
            $member = new EnumMember($this, $member, $offset);
        }

        $this->members->add($member);
    }

    /**
     * Remove a member of the enumerated type
     * @param  mixed  $offset  Member name
     */
    public function offsetUnset($offset)
    {
        $this->members->drop($offset);
    }

    /**
     * Create a new instance of the enumerated type
     * @param  null  $value  Member name
     * @return Enum
     */
    public function instance($value = null): Primitive
    {
        if (null === $value) {
            throw new InternalServerErrorException(
                'invalid_enumeration_value',
                'The value of an enumeration cannot be null'
            );
        }

        return new Enum($this, $value);
    }
}
