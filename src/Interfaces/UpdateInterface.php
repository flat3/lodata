<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Entity;
use Flat3\Lodata\Primitive;

interface UpdateInterface
{
    public function update(Primitive $key): Entity;
}