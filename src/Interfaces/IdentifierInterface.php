<?php

namespace Flat3\OData\Interfaces;

use Flat3\OData\Helper\Identifier;

interface IdentifierInterface
{
    public function getIdentifier(): Identifier;

    public function getTitle(): ?string;
}