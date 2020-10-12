<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Entity;
use Flat3\Lodata\PrimitiveType;

interface UpdateInterface
{
    public function update(PrimitiveType $key): Entity;
}