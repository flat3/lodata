<?php

declare(strict_types=1);

namespace Flat3\Lodata\Attributes;

use Attribute;
use Flat3\Lodata\Helper\Identifier;

#[Attribute(Attribute::TARGET_CLASS)]
class LodataIdentifier
{
    protected string $identifier;

    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier(): Identifier
    {
        return new Identifier($this->identifier);
    }
}