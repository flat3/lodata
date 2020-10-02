<?php

namespace Flat3\OData\Interfaces;

use Flat3\OData\Primitive;

interface FromPrimitiveInterface
{
    public function fromPrimitive(Primitive $primitive);
}