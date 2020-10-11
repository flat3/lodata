<?php

namespace Flat3\OData\Interfaces;

use Flat3\OData\Entity;
use Flat3\OData\PrimitiveType;

interface UpdateInterface
{
    public function update(PrimitiveType $key): Entity;
}