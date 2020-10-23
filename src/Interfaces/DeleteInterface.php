<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Primitive;

interface DeleteInterface
{
    public function delete(Primitive $key);
}