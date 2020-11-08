<?php

namespace Flat3\Lodata\Type;

use Flat3\Lodata\EnumerationType;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Helper\ObjectArray;
use Flat3\Lodata\Primitive;

/**
 * Enum
 * @package Flat3\Lodata\Type
 */
class Enum extends Primitive
{
    /** @var EnumerationType $type */
    protected $type;

    /** @var ObjectArray[Primitive] $value */
    protected $value;

    protected $nullable = false;

    public function __construct(EnumerationType $type, $value = null)
    {
        $this->value = new ObjectArray();
        $this->type = $type;
        parent::__construct($value);
    }

    public function toUrl(): string
    {
        return $this->getEnumerationValue();
    }

    public function getEnumerationValue(): string
    {
        $result = [];

        foreach ($this->value as $key => $value) {
            $result[] = $key;
        }

        return join(',', $result);
    }

    public function toJson(): string
    {
        return $this->getEnumerationValue();
    }

    public function set($value): self
    {
        $this->clear();
        return $this->add($value);
    }

    public function add($value): self
    {
        $values = is_string($value) ? explode(',', $value) : [$value];

        foreach ($values as $value) {
            if (!$this->type->getIsFlags()) {
                $this->value->clear();
            }

            /** @var EnumMember $member */
            $member = $this->type[$value];

            if ($member) {
                $this->value->add($member);
                continue;
            }

            foreach ($this->type->getMembers() as $member) {
                if ($member->getValue()->get() === $value) {
                    $this->value->add($member);
                    continue;
                }
            }

            throw new InternalServerErrorException(
                'invalid_enumeration_value',
                'Could not find the enumeration member for the provided value: '.$value
            );
        }

        return $this;
    }

    public function drop($value): self
    {
        $this->value->drop($value);

        return $this;
    }

    public function clear(): self
    {
        $this->value->clear();

        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->type->getIdentifier();
    }
}
