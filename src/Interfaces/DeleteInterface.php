<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\Helper\PropertyValue;

interface DeleteInterface
{
    public function delete(PropertyValue $key);
}