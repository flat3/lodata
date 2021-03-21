<?php

namespace Flat3\Lodata\Type;

/**
 * SByte
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class SByte extends Byte
{
    const identifier = 'Edm.SByte';
    public const format = 'c';
}
