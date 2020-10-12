<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\ComplexType;

interface ComplexTypeInterface
{
    public function getType(): ?ComplexType;

    public function setType(ComplexType $type);
}