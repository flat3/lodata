<?php

namespace Flat3\OData\Interfaces;

use Flat3\OData\PrimitiveType;

interface PrimitiveTypeInterface
{
    public function getType(): ?PrimitiveType;

    public function setType(PrimitiveType $type);
}