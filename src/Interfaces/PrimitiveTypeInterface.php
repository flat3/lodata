<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\PrimitiveType;

interface PrimitiveTypeInterface
{
    public function getType(): ?PrimitiveType;

    public function setType(PrimitiveType $type);
}