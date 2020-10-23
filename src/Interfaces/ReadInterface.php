<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Entity;
use Flat3\Lodata\Primitive;

interface ReadInterface
{
    public function read(Primitive $key): ?Entity;
}