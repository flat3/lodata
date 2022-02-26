<?php

declare(strict_types=1);

namespace Flat3\Lodata\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class LodataEnum
{
    protected string $name;
    protected string $enum;
    protected bool $isFlags = false;

    public function __construct(string $name, string $enum, ?bool $isFlags = false)
    {
        $this->name = $name;
        $this->enum = $enum;
        $this->isFlags = $isFlags;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEnum(): string
    {
        return $this->enum;
    }

    public function getIsFlags(): bool
    {
        return $this->isFlags;
    }
}