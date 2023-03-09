<?php

declare(strict_types=1);

namespace Flat3\Lodata\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class LodataRelationship
{
    protected ?string $name;
    protected ?string $description;
    protected bool $nullable = true;

    public function __construct(
        ?string $name = null,
        ?string $description = null,
        ?bool $nullable = true
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->nullable = $nullable;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }
}