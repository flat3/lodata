<?php

namespace Flat3\Lodata\Type;

class Int64 extends Byte
{
    const identifier = 'Edm.Int64';

    protected function repack($value)
    {
        return (int) $value;
    }
}
