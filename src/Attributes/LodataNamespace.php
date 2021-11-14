<?php

declare(strict_types=1);

namespace Flat3\Lodata\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class LodataNamespace
{
    protected ?string $name = null;

    public function __construct(string $name = null)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}