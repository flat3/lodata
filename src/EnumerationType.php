<?php

namespace Flat3\Lodata;

use ArrayAccess;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Traits\HasIdentifier;
use Flat3\Lodata\Type\Enum;
use Flat3\Lodata\Type\EnumMember;

class EnumerationType extends PrimitiveType implements ArrayAccess
{
    use HasIdentifier;

    /** @var ObjectArray[EnumMember] $members */
    protected $members;

    /** @var PrimitiveType $underlyingType */
    protected $underlyingType;

    /** @var bool $isFlags */
    protected $isFlags = false;

    public function __construct($identifier)
    {
        $this->setIdentifier($identifier);
        $this->members = new ObjectArray();
        $this->underlyingType = Type::int32();
        parent::__construct(Enum::class);
    }

    public static function factory($identifier): self
    {
        return new static($identifier);
    }

    public function getUnderlyingType(): Type
    {
        return $this->underlyingType;
    }

    public function setUnderlyingType(Type $type): self
    {
        $this->underlyingType = $type;

        return $this;
    }

    public function setIsFlags(bool $isFlags = true): self
    {
        $this->isFlags = $isFlags;

        return $this;
    }

    public function getIsFlags(): bool
    {
        return $this->isFlags;
    }

    public function getMembers(): ObjectArray
    {
        return $this->members;
    }

    public function offsetExists($offset)
    {
        return $this->members->exists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->members->get($offset);
    }

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

    public function offsetUnset($offset)
    {
        $this->members->drop($offset);
    }

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
