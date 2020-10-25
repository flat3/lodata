<?php

namespace Flat3\Lodata\Interfaces\EntitySet;

use Flat3\Lodata\Entity;
use Flat3\Lodata\Helper\PropertyValue;

interface UpdateInterface
{
    public function update(PropertyValue $key): Entity;
}