<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Entity;
use Flat3\Lodata\Primitive;
use Flat3\Lodata\PropertyValue;

interface UpdateInterface
{
    public function update(PropertyValue $key): Entity;
}