<?php

namespace Flat3\Lodata\Type;

/**
 * Int16
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class Int16 extends Byte
{
    const identifier = 'Edm.Int16';
    public const format = 's';
}
