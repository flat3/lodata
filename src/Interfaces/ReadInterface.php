<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Entity;
use Flat3\Lodata\PrimitiveType;

interface ReadInterface
{
    public function read(PrimitiveType $key): ?Entity;
}