<?php

namespace Flat3\Lodata\Type;

use Flat3\Lodata\Helper\Constants;

/**
 * SByte
 * @package Flat3\Lodata\Type
 * @method static self factory($value = null, ?bool $nullable = true)
 */
class SByte extends Byte
{
    const identifier = 'Edm.SByte';

    const openApiSchema = [
        'type' => Constants::OAPI_INTEGER,
        'format' => 'int8',
        'minimum' => -128,
        'maximum' => 127
    ];

    public const format = 'c';
}
