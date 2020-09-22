<?php

namespace Flat3\OData\Type;

class Int64 extends Byte
{
    public const EDM_TYPE = 'Edm.Int64';

    protected function repack($value)
    {
        return (int) $value;
    }
}
