<?php

declare(strict_types=1);

namespace Flat3\Lodata\Attributes;

use Flat3\Lodata\Annotation\Core\V1\Computed;
use Flat3\Lodata\Property;
use Flat3\Lodata\Type;

abstract class LodataProperty
{
    protected ?string $name;
    protected ?string $source = null;
    protected bool $key = false;
    protected bool $computed = false;
    protected bool $nullable = true;
    protected ?int $maxLength = null;
    protected ?int $precision = null;
    protected $scale = null;

    public function __construct(
        string $name,
        ?string $source = null,
        ?bool $key = false,
        ?bool $computed = false,
        ?bool $nullable = true,
        ?int $maxLength = null,
        ?int $precision = null,
        $scale = null
    ) {
        $this->name = $name;
        $this->source = $source;
        $this->key = $key;
        $this->computed = $computed;
        $this->nullable = $nullable;
        $this->maxLength = $maxLength;
        $this->precision = $precision;
        $this->scale = $scale;
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

    public function hasPrecision(): bool
    {
        return null !== $this->precision;
    }

    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    public function hasMaxLength(): bool
    {
        return null !== $this->maxLength;
    }

    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    public function hasScale(): bool
    {
        return null !== $this->scale;
    }

    public function getScale()
    {
        return $this->scale;
    }

    public function applyPropertyAttributes(Property $property): void
    {
        $property->setNullable($this->isNullable());

        if ($this->isComputed()) {
            $property->addAnnotation(new Computed);
        }

        if ($this->hasMaxLength()) {
            $property->setMaxLength($this->getMaxLength());
        }

        if ($this->hasPrecision()) {
            $property->setPrecision($this->getPrecision());
        }

        if ($this->hasScale()) {
            $property->setScale($this->getScale());
        }
    }

    abstract public function getType(): Type;
}