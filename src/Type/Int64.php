<?php

namespace Flat3\Lodata\Type;

class Int64 extends Byte
{
    protected $identifier = 'Edm.Int64';

    protected function repack($value)
    {
        return (int) $value;
    }
}
