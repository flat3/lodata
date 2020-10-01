<?php

namespace Flat3\OData\Interfaces;

use Flat3\OData\Type;

interface TypeInterface
{
    public function getType(): ?Type;

    public function getTypeName(): string;

    public function setType(Type $type);
}