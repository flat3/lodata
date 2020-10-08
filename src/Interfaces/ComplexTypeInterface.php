<?php

namespace Flat3\OData\Interfaces;

use Flat3\OData\ComplexType;

interface ComplexTypeInterface
{
    public function getType(): ?ComplexType;

    public function setType(ComplexType $type);
}