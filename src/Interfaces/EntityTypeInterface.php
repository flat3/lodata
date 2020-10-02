<?php

namespace Flat3\OData\Interfaces;

use Flat3\OData\Type\EntityType;

interface EntityTypeInterface
{
    public function getType(): ?EntityType;

    public function getTypeName(): string;

    public function setType(EntityType $type);
}