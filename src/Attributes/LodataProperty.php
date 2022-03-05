<?php

declare(strict_types=1);

namespace Flat3\Lodata\Attributes;

use Flat3\Lodata\Type;

abstract class LodataProperty
{
    protected ?string $name;
    protected ?string $source = null;
    protected bool $key = false;
    protected bool $computed = false;
    protected bool $nullable = true;

    public function __construct(
        string $name,
        ?string $source = null,
        ?bool $key = false,
        ?bool $computed = false,
        ?bool $nullable = true
    ) {
        $this->name = $name;
        $this->source = $source;
        $this->key = $key;
        $this->computed = $computed;
        $this->nullable = $nullable;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function hasSource(): bool
    {
        return null !== $this->source;
    }

    public function isKey(): bool
    {
        return $this->key;
    }

    public function isComputed(): bool
    {
        return $this->computed;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    abstract public function getType(): Type;
}