<?php

namespace Flat3\Lodata\Type;

/**
 * Int64
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class Int64 extends Byte
{
    const identifier = 'Edm.Int64';

    protected function repack($value)
    {
        return (int) $value;
    }
}
