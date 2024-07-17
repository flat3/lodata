<?php

declare(strict_types=1);

namespace Flat3\Lodata\Attributes;

use Flat3\Lodata\Annotation\Core\V1\Computed;
use Flat3\Lodata\Annotation\Core\V1\Description;
use Flat3\Lodata\Annotation\Core\V1\Immutable;
use Flat3\Lodata\DeclaredProperty;
use Flat3\Lodata\EntitySet;
use Flat3\Lodata\Property;
use Flat3\Lodata\Type;

abstract class LodataProperty
{
    protected ?string $name;
    protected ?string $description;
    protected ?string $source = null;
    protected bool $key = false;
    protected bool $computed = false;
    protected bool $immutable = false;
    protected bool $nullable = true;
    protected ?int $maxLength = null;
    protected ?int $precision = null;
    protected $scale = null;
    protected bool $alternativeKey = false;
    protected bool $searchable = false;
    protected bool $filterable = true;

    public function __construct(
        string $name,
        ?string $description = null,
        ?string $source = null,
        ?bool $key = false,
        ?bool $computed = false,
        ?bool $nullable = true,
        ?int $maxLength = null,
        ?int $precision = null,
        $scale = null,
        $alternativeKey = false,
        $searchable = false,
        $filterable = true,
        ?bool $immutable = false
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->source = $source;
        $this->key = $key;
        $this->computed = $computed;
        $this->immutable = $immutable;
        $this->nullable = $nullable;
        $this->maxLength = $maxLength;
        $this->precision = $precision;
        $this->scale = $scale;
        $this->alternativeKey = $alternativeKey;
        $this->searchable = $searchable;
        $this->filterable = $filterable;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
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

    public function isImmutable(): bool
    {
        return $this->immutable;
    }

    public function isNullable(): bool
    {
        return $this->nullable && !$this->key;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function isFilterable(): bool
    {
        return $this->filterable;
    }

    public function isAlternativeKey(): bool
    {
        return $this->alternativeKey;
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

    public function addProperty(EntitySet $entitySet): Property
    {
        $property = new DeclaredProperty($this->getName(), $this->getType());

        if ($this->isKey()) {
            $entitySet->getType()->setKey($property);
        } else {
            $entitySet->getType()->addProperty($property);
        }

        if ($this->hasSource()) {
            $entitySet->setPropertySourceName($property, $this->getSource());
        }

        $property->setNullable($this->isNullable());

        if ($this->getDescription()) {
            $property->addAnnotation(new Description($this->getDescription()));
        }

        if ($this->isComputed()) {
            $property->addAnnotation(new Computed);
        }

        if ($this->isImmutable()) {
            $property->addAnnotation(new Immutable);
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

        $property->setAlternativeKey($this->isAlternativeKey());
        $property->setFilterable($this->isFilterable());
        $property->setSearchable($this->isSearchable());

        return $property;
    }

    abstract public function getType(): Type;
}
