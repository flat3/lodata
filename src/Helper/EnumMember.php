<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper;

use Flat3\Lodata\EnumerationType;
use Flat3\Lodata\Interfaces\AnnotationInterface;
use Flat3\Lodata\Interfaces\NameInterface;
use Flat3\Lodata\Traits\HasAnnotations;
use Flat3\Lodata\Traits\HasName;

/**
 * Enum Member
 * @package Flat3\Lodata\Type
 */
class EnumMember implements NameInterface, AnnotationInterface
{
    use HasName;
    use HasAnnotations;

    const identifier = 'Edm.Member';

    /** @var EnumerationType $type */
    protected $type;

    /** @var int $value */
    protected $value = 0;

    public function __construct(EnumerationType $type)
    {
        $this->type = $type;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;

        return $this;
    }
}