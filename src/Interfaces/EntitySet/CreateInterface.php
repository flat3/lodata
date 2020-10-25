<?php

namespace Flat3\Lodata\Interfaces\EntitySet;

use Flat3\Lodata\Entity;

interface CreateInterface
{
    public function create(): Entity;
}