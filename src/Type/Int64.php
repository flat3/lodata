<?php

namespace Flat3\OData\Type;

class Int64 extends Byte
{
    protected $name = 'Edm.Int64';

    protected function repack($value)
    {
        return (int) $value;
    }
}
