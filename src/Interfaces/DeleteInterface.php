<?php

namespace Flat3\OData\Interfaces;

use Flat3\OData\PrimitiveType;

interface DeleteInterface
{
    public function delete(PrimitiveType $key);
}