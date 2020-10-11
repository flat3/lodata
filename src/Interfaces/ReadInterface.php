<?php

namespace Flat3\OData\Interfaces;

use Flat3\OData\Entity;
use Flat3\OData\PrimitiveType;

interface ReadInterface
{
    public function read(PrimitiveType $key): ?Entity;
}