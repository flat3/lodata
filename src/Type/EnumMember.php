<?php

namespace Flat3\Lodata\Type;

use Flat3\Lodata\EnumerationType;
use Flat3\Lodata\Interfaces\NameInterface;
use Flat3\Lodata\Traits\HasName;

/**
 * Enum Member
 * @package Flat3\Lodata\Type
 */
class EnumMember implements NameInterface
{
    use HasName;

    const identifier = 'Edm.Member';

    /** @var EnumerationType $type */
    protected $type;

    /** @var Byte $value */
    protected $value;

    public function __construct(EnumerationType $type, string $name, Byte $value)
    {
        $this->type = $type;
        $this->setName($name);
        $this->value = $value;
    }

    public function getValue(): Byte
    {
        return $this->value;
    }
}