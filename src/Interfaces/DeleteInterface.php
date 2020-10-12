<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\PrimitiveType;

interface DeleteInterface
{
    public function delete(PrimitiveType $key);
}